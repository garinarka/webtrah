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
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->roles->first()?->name,
                    // getAllPermissions() mencakup permission via role + langsung
                    // getPermissionNames() hanya return yang di-assign langsung → bug untuk admin/moderator/user
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'pendingApprovalsCount' => fn() => $user && ($user->isAdmin() || $user->isModerator())
                ? \App\Models\Approval::where('status', 'pending')->count()
                : 0,
            'unreadNotificationsCount' => fn() => $user
                ? $user->unreadNotifications()->count()
                : 0,
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'error'   => fn() => $request->session()->get('error'),
            ],
        ];
    }
}
