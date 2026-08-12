<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ini SEHARUSNYA sudah ada dari awal — dipakai luas di kode
     * (User::$fillable, User::person(), UserManagementController::
     * peopleWithoutUsers() & storeFromPerson()) tapi migration-nya
     * tidak pernah dibuat. Baru ketahuan sekarang lewat error
     * "column users.person_id does not exist" saat testing manual.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // UUID, bukan foreignId biasa — people.id juga UUID (lihat
            // migration create_people_table).
            $table->foreignUuid('person_id')
                ->nullable()
                ->after('id')
                ->constrained('people')
                // Kalau data person-nya dihapus, JANGAN ikut hapus akun
                // user-nya — cukup putus tautannya (jadi NULL). Akun
                // tetap ada, cuma tidak terhubung ke data anggota manapun.
                ->nullOnDelete();

            // 1 person cuma boleh punya 1 akun user (dicek juga manual
            // di storeFromPerson(), ini jaring pengaman di level DB).
            $table->unique('person_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
