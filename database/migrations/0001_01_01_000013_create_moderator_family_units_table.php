<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table: satu moderator bisa mengelola lebih dari satu unit (max 3)
        Schema::create('moderator_family_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('family_unit_id');
            $table->foreign('family_unit_id')->references('id')->on('family_units')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'family_unit_id']);
        });

        // Migrasi data lama: family_unit_id di users → pivot table
        if (Schema::hasColumn('users', 'family_unit_id')) {
            $rows = \Illuminate\Support\Facades\DB::table('users')
                ->whereNotNull('family_unit_id')
                ->get(['id', 'family_unit_id']);

            foreach ($rows as $row) {
                \Illuminate\Support\Facades\DB::table('moderator_family_units')->insertOrIgnore([
                    'user_id' => $row->id,
                    'family_unit_id' => $row->family_unit_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Drop family_unit_id dari users (sudah dipindah ke pivot)
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['family_unit_id']);
                $table->dropColumn('family_unit_id');
            });
        }
    }

    public function down(): void
    {
        // Restore family_unit_id ke users (ambil satu saja per user)
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('family_unit_id')->nullable()->after('remember_token');
            $table->foreign('family_unit_id')->references('id')->on('family_units')->nullOnDelete();
        });

        $rows = \Illuminate\Support\Facades\DB::table('moderator_family_units')->get();
        foreach ($rows as $row) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $row->user_id)
                ->update(['family_unit_id' => $row->family_unit_id]);
        }

        Schema::dropIfExists('moderator_family_units');
    }
};
