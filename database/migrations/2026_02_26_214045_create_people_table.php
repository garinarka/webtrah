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
        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_unit_id')->constrained('family_units')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['draft', 'pending', 'active', 'disputed', 'merged', 'archived'])->default('draft');
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');

            $table->date('birth_date')->nullable();
            $table->enum('birth_accuracy', ['exact', 'year_month', 'year', 'unknown'])->default('unknown');
            $table->date('death_date')->nullable();
            $table->enum('death_accuracy', ['exact', 'year_month', 'year', 'unknown'])->default('unknown');

            $table->string('display_name');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['family_unit_id', 'status']);
            $table->index('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
