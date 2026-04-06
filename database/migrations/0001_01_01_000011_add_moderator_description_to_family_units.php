<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_units', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            // moderator_id → FK ke users.id (bigint/unsignedBigInteger standar Laravel)
            $table->unsignedBigInteger('moderator_id')->nullable()->after('description');
            $table->foreign('moderator_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'family_unit_id')) {
                // family_units.id adalah UUID → harus pakai uuid() bukan string()
                $table->uuid('family_unit_id')->nullable()->after('remember_token');
                $table->foreign('family_unit_id')
                    ->references('id')
                    ->on('family_units')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['family_unit_id']);
            $table->dropColumn('family_unit_id');
        });

        Schema::table('family_units', function (Blueprint $table) {
            $table->dropForeign(['moderator_id']);
            $table->dropColumn(['description', 'moderator_id']);
        });
    }
};
