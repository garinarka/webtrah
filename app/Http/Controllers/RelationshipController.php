<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRelationshipRequest;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\Relationship;
use App\Models\SpouseUnit;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelationshipController extends Controller
{
    use AuthorizesRequests;

    /**
     * simpan relasi baru.
     *
     * logika mapping type:
     *  - jika user memilih "parent"  -> subject = related, object = person  (related adalah orang tua dari person)
     *  - jika user memilih "child"   -> subject = person, object = related  (person adalah orang tua dari related)
     *  - jika user memilih "spouse"  -> subject = person, object = related  (simetris)
     *  - step_parent / adopted_parent -> sama dengan parent
     *  - step_child  / adopted_child  -> sama dengan child
     */
    public function store(StoreRelationshipRequest $request)
    {
        $this->authorize('create', Relationship::class);

        $data     = $request->validated();
        $user     = auth()->user();
        $personId = $data['person_id'];
        $relatedId = $data['related_id'];
        $type     = $data['type'];

        // tentukan subject & object berdasarkan type
        [$subjectId, $objectId, $storedType] = match ($type) {
            'parent', 'step_parent', 'adopted_parent'
            => [$relatedId, $personId, $type === 'step_parent' ? 'step_parent' : ($type === 'adopted_parent' ? 'adopted_parent' : 'parent')],
            'child', 'step_child', 'adopted_child'
            => [$personId, $relatedId, $type === 'step_child' ? 'step_child' : ($type === 'adopted_child' ? 'adopted_child' : 'parent')],
            default // spouse
            => [$personId, $relatedId, 'spouse'],
        };

        // cek duplikat relasi aktif
        $exists = Relationship::where('subject_id', $subjectId)
            ->where('object_id', $objectId)
            ->where('type', $storedType)
            ->whereNull('ended_at')
            ->whereIn('status', ['approved', 'pending', 'draft'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['related_id' => 'Relasi ini sudah ada.']);
        }

        DB::beginTransaction();
        try {
            // untuk pasangan: buat spouse unit terlebih dahulu
            $spouseUnitId = null;
            if ($storedType === 'spouse' && !empty($data['started_at'])) {
                $spouseUnit   = SpouseUnit::create([
                    'started_at'   => $data['started_at'],
                    'ended_at'     => $data['ended_at'] ?? null,
                    'ended_reason' => $data['ended_reason'] ?? null,
                ]);
                $spouseUnitId = $spouseUnit->id;
            }

            // admin -> langsung approved, moderator -> pending
            $status = $user->isAdmin() ? 'approved' : 'pending';

            $relationship = Relationship::create([
                'subject_id'     => $subjectId,
                'object_id'      => $objectId,
                'type'           => $storedType,
                'spouse_unit_id' => $spouseUnitId,
                'is_biological'  => $data['is_biological'] ?? true,
                'started_at'     => $data['started_at'] ?? null,
                'ended_at'       => $data['ended_at'] ?? null,
                'ended_reason'   => $data['ended_reason'] ?? null,
                'status'         => $status,
                'approved_by'    => $user->isAdmin() ? $user->id : null,
                'approved_at'    => $user->isAdmin() ? now() : null,
                'created_by'     => $user->id,
            ]);

            // audit log pada kedua person
            $person = Person::find($personId);
            AuditLog::record($person, 'updated', [], [
                'relationship_added' => $storedType,
                'related_id'         => $relatedId,
                'status'             => $status,
            ]);

            DB::commit();

            $message = $user->isAdmin()
                ? 'Relasi berhasil ditambahkan.'
                : 'Relasi diajukan dan menunggu persetujuan admin.';

            // jika pending, notifikasi ke admin
            if ($status === 'pending') {
                // buat approval record untuk relasi juga agar bisa di-notif
                $approval = \App\Models\Approval::create([
                    'approvable_type' => Relationship::class,
                    'approvable_id'   => $relationship->id,
                    'action'          => 'create',
                    'changes'         => [
                        'type'        => ['old' => null, 'new' => $storedType],
                        'subject_id'  => ['old' => null, 'new' => $subjectId],
                        'object_id'   => ['old' => null, 'new' => $objectId],
                    ],
                    'status'       => 'pending',
                    'requested_by' => $user->id,
                ]);
                NotificationService::notifyAdminsOfNewApproval($approval->load('requester'));
            }

            return back()->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * hapus / ajukan penghapusan relasi.
     */
    public function destroy(Request $request, Relationship $relationship)
    {
        $this->authorize('delete', $relationship);

        $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            if ($user->isAdmin()) {
                // soft-end relasi (set ended_at) — lebih aman dari hard delete
                $relationship->update([
                    'ended_at'     => now()->toDateString(),
                    'ended_reason' => 'disownment', // generic
                    'status'       => 'rejected',
                ]);

                $person = Person::find($relationship->subject_id);
                AuditLog::record($person, 'updated', [], [
                    'relationship_removed' => $relationship->type,
                    'reason'               => $request->reason,
                ]);

                DB::commit();
                return back()->with('message', 'Relasi berhasil dihapus.');
            } else {
                // moderator: catat niat hapus di metadata, biarkan admin yang approve
                $relationship->update([
                    'metadata' => array_merge($relationship->metadata ?? [], [
                        'delete_requested_by'  => $user->id,
                        'delete_requested_at'  => now()->toIso8601String(),
                        'delete_reason'        => $request->reason,
                    ]),
                    'status' => 'pending',
                ]);

                DB::commit();
                return back()->with('message', 'Permintaan penghapusan relasi diajukan ke admin.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * approve relasi (admin only).
     */
    public function approve(Relationship $relationship)
    {
        $this->authorize('approve', $relationship);

        $relationship->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Relasi disetujui.');
    }
}
