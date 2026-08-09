<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database + storage/app tiap hari jam 2 pagi (traffic rendah).
// withoutOverlapping: kalau backup kemarin masih jalan (misal data
// membesar, prosesnya lambat), jangan tumpang tindih jalankan lagi.
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Cek kesehatan backup tiap pagi jam 7 — kirim notifikasi kalau backup
// terakhir lebih tua dari yang seharusnya (lihat monitor_backups di
// config/backup.php).
Schedule::command('backup:monitor')
    ->dailyAt('07:00');

// Hapus backup lama sesuai retensi di config/backup.php, jalan tiap
// hari jam 3 pagi (setelah backup baru selesai).
Schedule::command('backup:clean')
    ->dailyAt('03:00');
