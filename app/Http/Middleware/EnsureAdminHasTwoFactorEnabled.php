<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasTwoFactorEnabled
{
    /**
     * Wajibkan admin punya 2FA aktif sebelum bisa akses fitur admin.
     * Akun admin adalah target paling berharga (full access) — kalau
     * cuma dilindungi password, satu kebocoran password = full takeover.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        // Jangan sampai infinite redirect loop: izinkan akses ke
        // halaman setup/confirm 2FA itu sendiri, dan logout.
        $allowedRoutes = ['two-factor.setup', 'two-factor.confirm', 'logout'];
        if ($request->route() && in_array($request->route()->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup')
            ->with('status', 'Akun admin wajib mengaktifkan verifikasi 2 langkah (2FA) sebelum bisa melanjutkan.');
    }
}
