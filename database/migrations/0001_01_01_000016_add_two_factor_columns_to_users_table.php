<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Terenkripsi otomatis lewat cast 'encrypted' di model User —
            // JANGAN pernah simpan secret TOTP dalam bentuk plain text.
            $table->text('two_factor_secret')->nullable()->after('password');

            // Kode recovery (dipakai kalau HP/authenticator hilang),
            // juga terenkripsi.
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // Ditandai baru "aktif beneran" setelah user konfirmasi kode
            // pertama kali — bukan langsung pas generate secret.
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
