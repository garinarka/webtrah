<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Tambahkan HTTP security header ke setiap response.
     *
     * Header dasar (X-Frame-Options, X-Content-Type-Options, dst) selalu
     * aktif — aman di environment manapun, tidak mengganggu development.
     *
     * CSP & HSTS baru aktif saat APP_ENV=production, karena keduanya bisa
     * mematahkan Vite dev server (hot-reload dari origin localhost:5173)
     * kalau dipaksa aktif di lokal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if (app()->environment('production')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                // 'unsafe-inline' untuk style: dibutuhkan D3.js (tree visualisasi
                // set style lewat .style() langsung di elemen SVG) dan Vue :style
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net",
                // blob: dibutuhkan untuk preview hasil crop foto profil
                // (URL.createObjectURL) sebelum foto benar-benar di-upload
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));

            // HSTS hanya dikirim kalau request memang lewat HTTPS —
            // kirim HSTS di HTTP polos itu tidak ada efeknya dan berisiko
            // salah konfigurasi kalau HTTPS server belum siap.
            if ($request->secure()) {
                $response->headers->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains'
                );
            }
        }

        return $response;
    }
}
