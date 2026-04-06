<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->string('auditable_id');
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');

            $table->string('event');               // created, updated, deleted, draft_saved, submitted
            $table->jsonb('old_values')->default('{}');
            $table->jsonb('new_values')->default('{}');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
