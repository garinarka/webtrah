<?php

namespace App\Providers;

use App\Models\Relationship;
use App\Policies\RelationshipPolicy;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Relationship::class, RelationshipPolicy::class);

        // Perkuat endpoint /up bawaan Laravel: defaultnya cuma cek
        // aplikasi PHP hidup, tidak cek DB/queue. Event ini dipicu tiap
        // /up diakses — kalau exception dilempar di sini, /up otomatis
        // balas 500 (bukan 200), jadi monitoring luar (UptimeRobot,
        // dll) beneran tau kalau ada masalah nyata, bukan cuma PHP-nya
        // hidup doang.
        Event::listen(function (DiagnosingHealth $event) {
            // 1. Cek koneksi database beneran bisa query, bukan cuma
            //    config-nya ada.
            DB::connection()->getPdo();

            // 2. Cek backlog queue tidak menumpuk parah — indikasi kuat
            //    queue worker mati/berhenti kalau angka ini tinggi terus.
            $pendingJobs = DB::table('jobs')->count();
            if ($pendingJobs > 500) {
                throw new \RuntimeException(
                    "Queue backlog terlalu tinggi: {$pendingJobs} job pending. Queue worker kemungkinan mati."
                );
            }

            // 3. Cek job yang gagal permanen tidak menumpuk tanpa
            //    pernah dicek (indikasi ada bug yang belum ditangani).
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 50) {
                throw new \RuntimeException(
                    "Terlalu banyak failed job: {$failedJobs}. Perlu ditinjau."
                );
            }
        });
    }
}
