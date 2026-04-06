<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spouse_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->enum('ended_reason', ['divorce', 'death', 'annulment', 'separation'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spouse_units');
    }
};
