<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\Relationship;
use App\Models\SpouseUnit;
use App\Services\NotificationService;
use App\Services\RelationshipValidator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelationshipController extends Controller
{
    use AuthorizesRequests;

    /**
     * Simpan relasi baru.
     *
     * Type mapping:
     *  - parent / step_parent / adopted_parent → subject = related, object = person
     *  - child  / step_child  / adopted_child  → subject = person,  object = related
     *  - spouse                                → subject = person,  object = related
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'person_id' => ['required', 'exists:people,id'],
            'related_id' => ['required', 'exists:people,id', 'different:person_id'],
            'type' => ['required', 'in:parent,step_parent,adopted_parent,child,step_child,adopted_child,spouse'],
            'is_biological' => ['nullable', 'boolean'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'ended_reason' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $personId = $data['person_id'];
        $relatedId = $data['related_id'];
        $type = $data['type'];

        $person = Person::findOrFail($personId);
        $related = Person::findOrFail($relatedId);

        // Otorisasi: scope ke unit keluarga person, SAMA seperti PersonPolicy::update().
        $this->authorize('create', [Relationship::class, $person]);

        [$subjectId, $objectId, $storedType] = match ($type) {
            'parent', 'step_parent', 'adopted_parent' => [$relatedId, $personId, $type],
            'child', 'step_child', 'adopted_child' => [$personId, $relatedId, match ($type) {
                'step_child' => 'step_parent',
                'adopted_child' => 'adopted_parent',
                default => 'parent',
            }],
            default => [$personId, $relatedId, 'spouse'],
        };

        // Validasi bersama: same family unit + single active spouse.
        RelationshipValidator::assertValid($person, $related, $storedType, $subjectId, $objectId);

        // Cek duplikat
        $exists = Relationship::where('subject_id', $subjectId)
            ->where('object_id', $objectId)
            ->where('type', $storedType)
            ->whereNull('ended_at')
            ->whereIn('status', ['approved', 'pending'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['related_id' => 'Relasi ini sudah ada.']);
        }

        DB::beginTransaction();
        try {
            $spouseUnitId = null;
            if ($storedType === 'spouse' && ! empty($data['started_at'])) {
                $spouseUnit = SpouseUnit::create([
                    'started_at' => $data['started_at'],
                    'ended_at' => $data['ended_at'] ?? null,
                    'ended_reason' => $data['ended_reason'] ?? null,
                ]);
                $spouseUnitId = $spouseUnit->id;
            }

            $status = $user->isAdmin() ? 'approved' : 'pending';

            $relationship = Relationship::create([
                'subject_id' => $subjectId,
                'object_id' => $objectId,
                'type' => $storedType,
                'spouse_unit_id' => $spouseUnitId,
                'is_biological' => $data['is_biological'] ?? true,
                'started_at' => $data['started_at'] ?? null,
                'ended_at' => $data['ended_at'] ?? null,
                'ended_reason' => $data['ended_reason'] ?? null,
                'status' => $status,
                'approved_by' => $user->isAdmin() ? $user->id : null,
                'approved_at' => $user->isAdmin() ? now() : null,
                'created_by' => $user->id,
            ]);

            AuditLog::record(
                Person::find($personId),
                'updated',
                [],
                ['relationship_added' => $storedType, 'related_id' => $relatedId, 'status' => $status]
            );

            // Moderator: buat Approval record agar admin bisa setujui/tolak + kirim notif
            if (! $user->isAdmin()) {
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id' => $personId,
                    'action' => 'update',
                    'changes' => [
                        'relationship_added' => ['old' => null, 'new' => $storedType],
                        'related_id' => ['old' => null, 'new' => $relatedId],
                        'relationship_id' => ['old' => null, 'new' => $relationship->id],
                    ],
                    'status' => 'pending',
                    'requested_by' => $user->id,
                ]);
                NotificationService::notifyAdminsOfNewApproval(
                    $approval->load(['approvable', 'requester'])
                );
            }

            DB::commit();

            $message = $user->isAdmin()
                ? 'Relasi berhasil ditambahkan.'
                : 'Relasi diajukan dan menunggu persetujuan admin.';

            return back()->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hapus / ajukan penghapusan relasi.
     *
     * Admin    : soft-delete langsung (set ended_at).
     * Moderator: buat Approval action='delete_relation' dan notif admin.
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
                $relationship->update([
                    'ended_at' => now()->toDateString(),
                    'ended_reason' => $request->reason,
                    'status' => 'rejected',
                ]);

                $person = Person::find($relationship->subject_id);
                AuditLog::record($person, 'updated', [], [
                    'relationship_removed' => $relationship->type,
                    'reason' => $request->reason,
                ]);

                DB::commit();

                return back()->with('message', 'Relasi berhasil dihapus.');
            }

            // Moderator: buat Approval record agar masuk queue + notif admin
            // Gunakan subject Person sebagai approvable
            $person = Person::find($relationship->subject_id);
            $approval = Approval::create([
                'approvable_type' => Person::class,
                'approvable_id' => $relationship->subject_id,
                'action' => 'delete_relation',
                'changes' => [
                    'relationship_id' => ['old' => $relationship->id,   'new' => null],
                    'type' => ['old' => $relationship->type,  'new' => null],
                    'subject_id' => ['old' => $relationship->subject_id, 'new' => null],
                    'object_id' => ['old' => $relationship->object_id,  'new' => null],
                    'reason' => ['old' => null, 'new' => $request->reason],
                ],
                'status' => 'pending',
                'requested_by' => $user->id,
            ]);

            // Tandai relasi sebagai pending (ada permintaan hapus)
            $relationship->update(['status' => 'pending']);

            AuditLog::record($person, 'update_requested', [], [
                'delete_relation_requested' => $relationship->type,
                'reason' => $request->reason,
            ]);

            NotificationService::notifyAdminsOfNewApproval(
                $approval->load(['approvable', 'requester'])
            );

            DB::commit();

            return back()->with('message', 'Permintaan penghapusan relasi diajukan ke admin.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Batalkan pengajuan relasi yang masih pending (tanpa approval admin).
     * Hanya pemohon (created_by) atau admin yang bisa membatalkan.
     */
    public function cancel(Relationship $relationship)
    {
        $user = auth()->user();

        if ($relationship->status !== 'pending') {
            return back()->withErrors(['error' => 'Hanya relasi pending yang bisa dibatalkan.']);
        }

        if (! $user->isAdmin() && $relationship->created_by !== $user->id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            // Hapus Approval terkait (jika ada) sebelum hapus relasi
            \App\Models\Approval::where('status', 'pending')
                ->get()
                ->each(function ($approval) use ($relationship) {
                    $relId = $approval->changes['relationship_id']['new']
                          ?? $approval->changes['relationship_id']['old']
                          ?? null;
                    if ($relId === $relationship->id) {
                        $approval->delete();
                    }
                });

            $relationship->delete();

            DB::commit();

            return back()->with('message', 'Pengajuan relasi berhasil dibatalkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approve(Relationship $relationship)
    {
        $this->authorize('approve', $relationship);

        $relationship->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Relasi disetujui.');
    }
}
