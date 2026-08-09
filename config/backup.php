<?php

return [

    'backup' => [

        'name' => env('APP_NAME', 'webtrah'),

        'source' => [
            'files' => [
                'include' => [
                    // Cuma backup yang benar-benar tidak bisa digenerate ulang.
                    // storage/app: file upload/export user.
                    // .env: WAJIB di-backup terpisah juga secara manual/aman,
                    // TAPI kita exclude dari backup otomatis ini supaya
                    // secret production tidak numpuk di banyak file zip.
                    base_path('storage/app'),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path(), // hindari backup nested (log, cache, dll)
                    base_path('storage/app/public'), // simlink, sudah di-include lewat storage/app kalau perlu
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            // Database di-backup lewat pg_dump, terpisah dari file di atas.
            'databases' => [
                'pgsql',
            ],
        ],

        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        'destination' => [
            'compression_method' => \ZipArchive::CM_DEFAULT,
            'compression_level' => 9,

            // 'local' dulu karena hosting belum ditentukan. Begitu sudah
            // pilih hosting/cloud storage, tinggal ganti FILESYSTEM_DISK
            // di .env jadi 's3' (atau disk cloud lain) TANPA ubah kode
            // apa pun di sini — itu tujuan didesain seperti ini dari awal.
            'disks' => [
                env('BACKUP_DISK', 'local'),
            ],

            'filename_prefix' => '',
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            // Sengaja TIDAK notify tiap backup sukses — biar tidak
            // spam email tiap hari. Cuma dikabari kalau ADA MASALAH.
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@webtrah.test'),
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'webtrah'),
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                // Alarm kalau backup TERBARU lebih tua dari 26 jam
                // (harusnya tiap hari, kasih toleransi 2 jam).
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 2,
                // Alarm kalau total backup di disk membengkak lewat batas
                // (mencegah disk penuh kalau retensi/cleanup gagal jalan).
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            // Retensi: simpan backup harian 7 hari terakhir penuh,
            // lalu makin jarang makin lama — supaya storage tidak
            // membengkak tanpa batas tapi tetap ada histori jangka panjang.
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
