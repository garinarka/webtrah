<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use App\Models\Person;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Approval::with(['approvable', 'requester'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        if ($request->user()->isModerator() && !$request->user()->isAdmin()) {
            $query->whereHas('approvable', function ($q) use ($request) {
                $q->where('family_unit_id', $request->user()->family_unit_id);
            });
        }

        $approvals = $query->paginate(10);

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'can' => [
                'approve' => $request->user()->can('approve_changes'),
                'reject' => $request->user()->can('reject_changes'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Approval $approval)
    {
        $this->authorize('view', $approval);

        $user = auth()->user();

        return Inertia::render('Approvals/Show', [
            'approval' => $approval->load(['approvable', 'requester', 'approver']),
            'diff' => $this->formatDiff($approval),
            'can' => [
                'approve' => $user->can('approve', $approval),
                'reject' => $user->can('reject', $approval),
            ]
        ]);
    }

    public function approve(Request $request, Approval $approval)
    {
        $this->authorize('approve', $approval);

        $request->validate([
            'confirmation' => 'required|in:SETUJU',
        ]);

        if ($approval->action === 'create') {
            $approval->approvable->update(['status' => 'active']);
        } elseif ($approval->action === 'update') {
            foreach ($approval->changes as $field => $values) {
                $approval->approvable->{$field} = $values['new'];
            }
            $approval->approvable->save();
        } elseif ($approval->action === 'delete') {
            $approval->approvable->delete();
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('approvals.index')->with('message', 'Perubahan disetujui');
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

        return redirect()->route('approvals.index')->with('message', 'Perubahan ditolak');
    }

    private function formatDiff(Approval $approval): array
    {
        $diff = [];
        foreach ($approval->changes as $field => $values) {
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
        $labels = [
            'display_name' => 'Nama',
            'birth_date' => 'Tanggal Lahir',
            'gender' => 'Jenis Kelamin',
            'status' => 'Status',
        ];
        return $labels[$field] ?? $field;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
