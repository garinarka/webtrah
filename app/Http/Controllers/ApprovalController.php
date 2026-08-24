<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Person;
use App\Notifications\ApprovalDecided;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        /**
         * Orphan check: approval yang approvable-nya sudah null (soft-deleted oleh admin)
         * di-auto-reject agar tidak menggantung di queue.
         * Ini terjadi misal: moderator ajukan delete → admin hapus langsung via UI
         * → approval queue masih ada tapi person sudah hilang.
         */
        // Only check Person-type approvables for orphan status
        Approval::with('approvable')
            ->where('status', 'pending')
            ->where('approvable_type', \App\Models\Person::class)
            ->get()
            ->each(function (Approval $approval) {
                if (is_null($approval->approvable)) {
                    $approval->update([
                        'status' => 'rejected',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'rejection_reason' => 'Data yang dimaksud sudah tidak ada (dihapus sebelum ditinjau).',
                    ]);
                }
            });

        $user = auth()->user();
        $query = Approval::with(['approvable.familyUnit:id,name', 'requester:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        // Moderator HANYA melihat approval yang mereka ajukan sendiri.
        // Admin yang bisa lihat SEMUA pengajuan pending (tidak difilter
        // di sini — lihat kondisi if di bawah, cuma berlaku utk moderator).
        if ($user->isModerator()) {
            $query->where('requested_by', $user->id);
        }

        $approvals = $query->paginate(15);

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'can' => [
                // Approve/reject cuma admin — moderator tidak pernah bisa,
                // apapun approval-nya (lihat ApprovalPolicy::approve()).
                'approve' => $user->isAdmin(),
                'reject' => $user->isAdmin(),
            ],
        ]);
    }

    public function show(Approval $approval)
    {
        $this->authorize('view', $approval);

        $user = auth()->user();

        $approval->load(['approvable', 'requester', 'approver']);

        return Inertia::render('Approvals/Show', [
            'approval' => $approval,
            'approvableExists' => ! is_null($approval->approvable),
            'diff' => $this->formatDiff($approval),
            'can' => [
                'approve' => $user->can('approve', $approval),
                'reject' => $user->can('reject', $approval),
                // Pengaju bisa cancel approvalnya sendiri jika masih pending.
                // Admin SENGAJA tidak diberi akses cancel (beda dari
                // approve/reject) — cuma pembuat pengajuan yang boleh.
                'cancel' => $approval->status === 'pending'
                    && (int) $approval->requested_by === (int) $user->id,
            ],
        ]);
    }

    public function approve(Request $request, Approval $approval)
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'confirmation' => 'required|in:SETUJU',
        ]);

        if ($approval->action === 'create') {
            $updateData = ['status' => 'active'];
            foreach ($approval->changes ?? [] as $field => $values) {
                if ($field === 'relationship_ids') {
                    continue; // ditangani terpisah di bawah, bukan field Person
                }
                if (isset($values['new']) && ! in_array($field, ['status', 'created_by'])) {
                    $updateData[$field] = $values['new'];
                }
            }
            $approval->approvable?->update($updateData);

            // Aktifkan relasi yang di-set bersamaan di wizard step 3 (lihat PersonController::store()).
            $relationshipIds = $approval->changes['relationship_ids']['new'] ?? [];
            if (! empty($relationshipIds)) {
                \App\Models\Relationship::whereIn('id', $relationshipIds)->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

        } elseif ($approval->action === 'update') {
            if ($approval->approvable) {
                // Deteksi apakah ini update relasi (bukan update field Person biasa)
                $isRelationUpdate = isset($approval->changes['relationship_added'])
                    || isset($approval->changes['relationship_id']);

                if ($isRelationUpdate) {
                    // Setujui: ubah status Relationship dari pending → approved
                    $relId = $approval->changes['relationship_id']['new']
                          ?? $approval->changes['relationship_id']['old']
                          ?? null;
                    if ($relId) {
                        \App\Models\Relationship::where('id', $relId)->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }
                } else {
                    // Update field biasa pada Person
                    $personFields = ['display_name', 'gender', 'birth_date', 'birth_accuracy',
                        'death_date', 'death_accuracy', 'family_unit_id'];
                    foreach ($approval->changes as $field => $values) {
                        if (in_array($field, $personFields)) {
                            $approval->approvable->{$field} = $values['new'];
                        }
                    }
                    $approval->approvable->save();
                }
            }

        } elseif ($approval->action === 'delete') {
            if ($approval->approvable) {
                // Opsi A: cascade — akhiri otomatis relasi pasangan aktif SEBELUM person dihapus.
                \App\Services\RelationshipValidator::cascadeEndSpouseRelationships($approval->approvable);
            }
            $approval->approvable?->delete();

        } elseif ($approval->action === 'resign_moderator') {
            // Approvable null untuk resign — ambil user dari requested_by
            $moderator = \App\Models\User::find($approval->requested_by);
            $unitId = $approval->changes['family_unit_id']['old'] ?? null;
            if ($moderator && $unitId) {
                $moderator->managedFamilyUnits()->detach($unitId);
                \App\Models\FamilyUnit::where('id', $unitId)
                    ->where('moderator_id', $moderator->id)
                    ->update(['moderator_id' => null]);
            }

        } elseif ($approval->action === 'delete_relation') {
            // Hapus relasi yang diminta moderator
            // ended_reason harus salah satu dari enum: divorce|death|annulment|separation|disownment
            $relId = $approval->changes['relationship_id']['old'] ?? null;
            if ($relId) {
                \App\Models\Relationship::where('id', $relId)->update([
                    'ended_at' => now()->toDateString(),
                    'ended_reason' => 'disownment', // enum value — berarti "diputus/dihapus"
                    'status' => 'rejected',
                ]);
            }
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $requester = $approval->requester;
        if ($requester) {
            $requester->notify(new ApprovalDecided($approval->fresh(['approvable', 'approver']), 'approved'));
        }

        return redirect()->route('approvals.index')
            ->with('message', 'Perubahan berhasil disetujui.');
    }

    /**
     * Batalkan approval yang masih pending — hanya oleh si pengaju atau admin.
     * Untuk action 'create': person pending ikut dihapus.
     * Untuk action 'update'/'delete'/'delete_relation': approval dihapus, data asli tidak berubah.
     */
    public function cancel(Approval $approval)
    {
        $user = auth()->user();

        if ($approval->status !== 'pending') {
            return back()->withErrors(['error' => 'Hanya approval yang masih pending yang bisa dibatalkan.']);
        }

        // HANYA pengaju sendiri — admin TIDAK diberi akses cancel di sini,
        // karena tindakan ini secara sengaja dibatasi cuma milik pembuat
        // pengajuan (beda dari approve/reject yang memang hak admin).
        if ((int) $approval->requested_by !== (int) $user->id) {
            abort(403, 'Tidak berwenang membatalkan pengajuan ini.');
        }

        DB::beginTransaction();
        try {
            if ($approval->action === 'create') {
                // Hapus relasi pending yang di-set bersamaan di wizard step 3 (jika ada)
                // SEBELUM person-nya dihapus, supaya tidak ada relasi menggantung.
                $relationshipIds = $approval->changes['relationship_ids']['new'] ?? [];
                if (! empty($relationshipIds)) {
                    \App\Models\Relationship::whereIn('id', $relationshipIds)->delete();
                }

                // Hapus person yang masih pending (belum aktif)
                $approval->approvable?->delete();
            }
            // Untuk update/delete/delete_relation: cukup hapus approval-nya,
            // data asli tidak disentuh

            $approval->delete();

            DB::commit();

            return redirect()->back()->with('message', 'Pengajuan berhasil dibatalkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reject(Request $request, Approval $approval)
    {
        $this->authorize('reject', $approval);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $approval->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        // Kalau pengajuan 'create' ditolak dan ada relasi yang di-set bersamaan di
        // wizard step 3, relasi pending itu ikut dibatalkan (bukan diaktifkan)
        // supaya tidak menggantung menunjuk ke person yang tidak pernah aktif.
        if ($approval->action === 'create') {
            $relationshipIds = $approval->changes['relationship_ids']['new'] ?? [];
            if (! empty($relationshipIds)) {
                \App\Models\Relationship::whereIn('id', $relationshipIds)->update([
                    'status' => 'rejected',
                    'ended_at' => now()->toDateString(),
                    'ended_reason' => 'disownment',
                ]);
            }
        }

        $requester = $approval->requester;
        if ($requester) {
            $requester->notify(new ApprovalDecided($approval->fresh(['approvable', 'approver']), 'rejected', $request->reason));
        }

        return redirect()->route('approvals.index')
            ->with('message', 'Perubahan ditolak.');
    }

    private function formatDiff(Approval $approval): array
    {
        $diff = [];
        foreach ($approval->changes ?? [] as $field => $values) {
            if ($field === 'relationship_ids') {
                continue; // ditampilkan terpisah, bukan sebagai diff field biasa
            }
            $diff[] = [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'old' => $values['old'] ?? '-',
                'new' => $values['new'] ?? '-',
            ];
        }

        return $diff;
    }

    private function fieldLabel(string $field): string
    {
        return [
            'display_name' => 'Nama',
            'birth_date' => 'Tanggal Lahir',
            'birth_accuracy' => 'Akurasi Lahir',
            'death_date' => 'Tanggal Meninggal',
            'death_accuracy' => 'Akurasi Meninggal',
            'gender' => 'Jenis Kelamin',
            'status' => 'Status',
            'family_unit_id' => 'Unit Keluarga',
            'relationship_id' => 'ID Relasi',
            'type' => 'Jenis Relasi',
            'subject_id' => 'Pihak Pertama',
            'object_id' => 'Pihak Kedua',
            'reason' => 'Alasan',
            'moderator_name' => 'Nama Moderator',
            'unit_name' => 'Unit Keluarga',
            'relationship_added' => 'Relasi Ditambahkan',
        ][$field] ?? $field;
    }
}
