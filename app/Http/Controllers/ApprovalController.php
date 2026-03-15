<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Person;
use App\Notifications\ApprovalDecided;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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
        Approval::with('approvable')
            ->where('status', 'pending')
            ->get()
            ->each(function (Approval $approval) {
                if (is_null($approval->approvable)) {
                    $approval->update([
                        'status'           => 'rejected',
                        'approved_by'      => auth()->id(),
                        'approved_at'      => now(),
                        'rejection_reason' => 'Data yang dimaksud sudah tidak ada (dihapus sebelum ditinjau).',
                    ]);
                }
            });

        $query = Approval::with(['approvable.familyUnit:id,name', 'requester:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        $approvals = $query->paginate(15);

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'can'       => [
                'approve' => $request->user()->can('approve_changes'),
                'reject'  => $request->user()->can('reject_changes'),
            ],
        ]);
    }

    public function show(Approval $approval)
    {
        $this->authorize('view', $approval);

        $user = auth()->user();

        $approval->load(['approvable', 'requester', 'approver']);

        return Inertia::render('Approvals/Show', [
            'approval'         => $approval,
            'approvableExists' => !is_null($approval->approvable),
            'diff'             => $this->formatDiff($approval),
            'can'              => [
                'approve' => $user->can('approve', $approval),
                'reject'  => $user->can('reject', $approval),
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
                if (isset($values['new']) && !in_array($field, ['status', 'created_by'])) {
                    $updateData[$field] = $values['new'];
                }
            }
            $approval->approvable?->update($updateData);
        } elseif ($approval->action === 'update') {
            if ($approval->approvable) {
                foreach ($approval->changes as $field => $values) {
                    $approval->approvable->{$field} = $values['new'];
                }
                $approval->approvable->save();
            }
        } elseif ($approval->action === 'delete') {
            $approval->approvable?->delete();
        }

        $approval->update([
            'status'      => 'approved',
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

    public function reject(Request $request, Approval $approval)
    {
        $this->authorize('reject', $approval);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $approval->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

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
            $diff[] = [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'old'   => $values['old'] ?? '-',
                'new'   => $values['new'] ?? '-',
            ];
        }
        return $diff;
    }

    private function fieldLabel(string $field): string
    {
        return [
            'display_name'   => 'Nama',
            'birth_date'     => 'Tanggal Lahir',
            'birth_accuracy' => 'Akurasi Lahir',
            'death_date'     => 'Tanggal Meninggal',
            'death_accuracy' => 'Akurasi Meninggal',
            'gender'         => 'Jenis Kelamin',
            'status'         => 'Status',
            'family_unit_id' => 'Unit Keluarga',
        ][$field] ?? $field;
    }
}
