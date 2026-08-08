<?php

return [

    // DSN diambil dari .env (SENTRY_LARAVEL_DSN), jangan hardcode di sini.
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Berapa % error yang dikirim. 1.0 = kirim semua (project masih kecil,
    // volume error rendah, jadi aman kirim 100%).
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    // Tracing (Performance Monitoring) DIMATIKAN dulu — belum perlu di
    // tahap ini, dan supaya kuota gratis Sentry tidak boros dipakai
    // fitur yang belum kita butuhkan. Aktifkan nanti kalau sudah live
    // dan butuh investigasi performa (ubah ke angka 0.1–0.2 = 10–20%
    // sample, jangan langsung 1.0 di traffic nyata).
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    // Profiling DIMATIKAN — fitur lanjutan, belum relevan untuk
    // project solo developer di tahap ini.
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // JANGAN kirim data pribadi user (email, IP, dll) otomatis ke Sentry —
    // penting untuk privasi data keluarga yang disimpan Webtrah.
    'send_default_pii' => false,

    // Environment supaya error dari lokal/testing tidak nyampur dengan
    // error user asli di dashboard Sentry.
    'environment' => env('APP_ENV', 'production'),

    // Breadcrumbs: jejak aktivitas sebelum error terjadi (query, request,
    // log) — sangat membantu debugging, biarkan default aktif.
    'breadcrumbs' => [
        'sql_queries' => true,
        'sql_bindings' => false, // jangan kirim isi data query (privasi)
        'queue_info' => true,
        'command_info' => true,
    ],
];
