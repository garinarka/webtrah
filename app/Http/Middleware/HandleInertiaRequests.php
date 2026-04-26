<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    // getAllPermissions() mencakup permission via role + langsung
                    // getPermissionNames() hanya return yang di-assign langsung → bug untuk admin/moderator/user
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    // IDs unit keluarga yang dikelola moderator (array, max 3)
                    // Admin: null (semua unit), User: null (tidak ada)
                    // Query langsung ke pivot table untuk menghindari ambiguitas kolom 'id'
                    'managedFamilyUnitIds' => $user->isModerator()
                        ? \Illuminate\Support\Facades\DB::table('moderator_family_units')
                            ->where('user_id', $user->id)
                            ->pluck('family_unit_id')
                            ->toArray()
                        : null,
                ] : null,
            ],
            'pendingApprovalsCount' => function () use ($user) {
                if (! $user) {
                    return 0;
                }
                if ($user->isAdmin()) {
                    return \App\Models\Approval::where('status', 'pending')->count();
                }
                if ($user->isModerator()) {
                    $unitIds = \Illuminate\Support\Facades\DB::table('moderator_family_units')
                        ->where('user_id', $user->id)
                        ->pluck('family_unit_id')
                        ->toArray();
                    if (empty($unitIds)) {
                        return 0;
                    }

                    return \App\Models\Approval::where('status', 'pending')
                        ->whereHasMorph('approvable', [\App\Models\Person::class], function ($q) use ($unitIds) {
                            $q->whereIn('family_unit_id', $unitIds);
                        })
                        ->count();
                }

                return 0;
            },
            'unreadNotificationsCount' => fn () => $user
                ? $user->unreadNotifications()->count()
                : 0,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
