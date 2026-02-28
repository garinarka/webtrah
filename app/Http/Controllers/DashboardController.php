<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return Inertia::render('Dashboard/Index', [
                'stats' => $this->adminStats(),
            ]);
        }

        if ($user->isModerator()) {
            return Inertia::render('Dashboard/Index', [
                'stats' => $this->moderatorStats($user),
            ]);
        }

        return Inertia::render('Dashboard/Index', [
            'stats' => $this->userStats($user),
        ]);
    }

    // ── ADMIN STATS ───────────────────────────────────────────────────────

    private function adminStats(): array
    {
        $totalPeople   = Person::whereIn('status', ['active', 'pending', 'draft'])->count();
        $activePeople  = Person::where('status', 'active')->count();
        $pendingCount  = Approval::where('status', 'pending')->count();
        $totalUsers    = User::count();

        // perubahan 30 hari terakhir
        $recentChanges = AuditLog::where('created_at', '>=', now()->subDays(30))->count();

        // persetujuan 30 hari terakhir
        $approvedRecently = Approval::where('status', 'approved')
            ->where('approved_at', '>=', now()->subDays(30))
            ->count();

        // aktivitas terbaru (10 terakhir) — cast auditable_id ke uuid untuk postgresql
        $recentActivity = AuditLog::with('user:id,name')
            ->where('auditable_type', Person::class)
            ->whereRaw('"auditable_id"::uuid IN (SELECT id FROM people WHERE deleted_at IS NULL)')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'event'      => $log->event,
                'model_name' => $log->new_values['display_name'] ?? $log->old_values['display_name'] ?? 'Data',
                'user_name'  => $log->user?->name ?? 'System',
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        // antrian approval terbaru
        $pendingApprovals = Approval::with(['approvable:id,display_name', 'requester:id,name'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id'           => $a->id,
                'action'       => $a->action,
                'person_name'  => $a->approvable?->display_name ?? 'Data Baru',
                'requester'    => $a->requester?->name ?? '-',
                'created_at'   => $a->created_at->toIso8601String(),
            ]);

        return [
            'role'             => 'admin',
            'total_people'     => $totalPeople,
            'active_people'    => $activePeople,
            'pending_count'    => $pendingCount,
            'total_users'      => $totalUsers,
            'recent_changes'   => $recentChanges,
            'approved_recently' => $approvedRecently,
            'recent_activity'  => $recentActivity,
            'pending_approvals' => $pendingApprovals,
        ];
    }

    // ── MODERATOR STATS ───────────────────────────────────────────────────

    private function moderatorStats(User $user): array
    {
        // moderator lihat semua (karena family_unit_id tidak ada di users)
        $totalPeople  = Person::whereIn('status', ['active', 'pending'])->count();
        $activePeople = Person::where('status', 'active')->count();
        $pendingCount = Approval::where('status', 'pending')->count();

        $recentChanges = AuditLog::where('created_at', '>=', now()->subDays(30))->count();

        $pendingApprovals = Approval::with(['approvable:id,display_name', 'requester:id,name'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'action'      => $a->action,
                'person_name' => $a->approvable?->display_name ?? 'Data Baru',
                'requester'   => $a->requester?->name ?? '-',
                'created_at'  => $a->created_at->toIso8601String(),
            ]);

        $recentActivity = AuditLog::with('user:id,name')
            ->where('auditable_type', Person::class)
            ->whereRaw('"auditable_id"::uuid IN (SELECT id FROM people WHERE deleted_at IS NULL)')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'event'      => $log->event,
                'model_name' => $log->new_values['display_name'] ?? $log->old_values['display_name'] ?? 'Data',
                'user_name'  => $log->user?->name ?? 'System',
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return [
            'role'             => 'moderator',
            'total_people'     => $totalPeople,
            'active_people'    => $activePeople,
            'pending_count'    => $pendingCount,
            'recent_changes'   => $recentChanges,
            'pending_approvals' => $pendingApprovals,
            'recent_activity'  => $recentActivity,
        ];
    }

    // ── USER STATS ────────────────────────────────────────────────────────

    private function userStats(User $user): array
    {
        $totalActive   = Person::where('status', 'active')->count();
        $mySubmissions = Person::where('created_by', $user->id)->count();
        $myPending     = Person::where('created_by', $user->id)
            ->where('status', 'pending')
            ->count();
        $myDrafts      = Person::where('created_by', $user->id)
            ->where('status', 'draft')
            ->count();

        $myActivity = AuditLog::with('user:id,name')
            ->where('user_id', $user->id)
            ->where('auditable_type', Person::class)
            ->whereRaw('"auditable_id"::uuid IN (SELECT id FROM people WHERE deleted_at IS NULL)')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'event'      => $log->event,
                'model_name' => $log->new_values['display_name'] ?? $log->old_values['display_name'] ?? 'Data',
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return [
            'role'          => 'user',
            'total_active'  => $totalActive,
            'my_submissions' => $mySubmissions,
            'my_pending'    => $myPending,
            'my_drafts'     => $myDrafts,
            'my_activity'   => $myActivity,
        ];
    }
}
