<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
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
                    'permissions' => $user->getPermissionNames(),
                ] : null,
            ],
            // junlah approval pending — dibaca sekali per request, dipakai sidebar & widget
            'pendingApprovalsCount' => fn() => $user && ($user->isAdmin() || $user->isModerator())
                ? \App\Models\Approval::where('status', 'pending')->count()
                : 0,
            // unread notifications count untuk bell icon
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
