<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('people')->cascadeOnDelete();
            $table->foreignUuid('object_id')->constrained('people')->cascadeOnDelete();
            $table->enum('type', ['parent', 'child', 'spouse', 'step_parent', 'step_child', 'adopted_parent', 'adopted_child']);
            $table->foreignUuid('spouse_unit_id')->nullable()->constrained('spouse_units')->nullOnDelete();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->enum('ended_reason', ['divorce', 'death', 'annulment', 'separation', 'disownment'])->nullable();
            $table->boolean('is_biological')->default(true);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'disputed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subject_id', 'object_id', 'type', 'ended_at'], 'unique_active_relation');
            $table->index(['subject_id', 'status', 'ended_at']);
            $table->index(['object_id', 'status', 'ended_at']);
        });

        $schema = config('database.connections.pgsql.schema') ?? 'public';

        DB::statement("
            ALTER TABLE {$schema}.relationships
            ADD CONSTRAINT no_self_relation
            CHECK (subject_id <> object_id)
        ");
    }

    public function down(): void
    {
        /**
         * Reverse the migrations.
         */
        Schema::dropIfExists('relationships');
    }
};
