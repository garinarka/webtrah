<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            // Global, bukan per-route: admin punya hak istimewa di
            // BANYAK tempat (lihat isAdmin() bypass di PersonPolicy,
            // FamilyUnitPolicy, dll), bukan cuma /admin/users. Middleware
            // ini sendiri no-op untuk guest & non-admin, jadi aman
            // dipasang global tanpa dampak ke user/moderator biasa.
            \App\Http\Middleware\EnsureAdminHasTwoFactorEnabled::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'require.2fa' => \App\Http\Middleware\EnsureAdminHasTwoFactorEnabled::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Kirim semua exception yang tidak tertangani ke Sentry.
        // Integration::handles() otomatis skip exception yang memang
        // "normal" (404, validation error, dsb) — cuma error beneran
        // yang dikirim, biar dashboard tidak berisik.
        Integration::handles($exceptions);
    })->create();
