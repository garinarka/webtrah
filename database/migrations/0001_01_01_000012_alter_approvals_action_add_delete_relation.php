<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL enum tidak bisa di-ALTER secara langsung.
        // Solusi: drop constraint lama, tambahkan column check baru.
        DB::statement('ALTER TABLE approvals DROP CONSTRAINT IF EXISTS approvals_action_check');
        DB::statement("ALTER TABLE approvals ADD CONSTRAINT approvals_action_check CHECK (action IN ('create','update','delete','delete_relation'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE approvals DROP CONSTRAINT IF EXISTS approvals_action_check');
        DB::statement("ALTER TABLE approvals ADD CONSTRAINT approvals_action_check CHECK (action IN ('create','update','delete'))");
    }
};
