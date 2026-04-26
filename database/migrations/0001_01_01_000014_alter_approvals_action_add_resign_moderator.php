<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE approvals DROP CONSTRAINT IF EXISTS approvals_action_check');
        DB::statement("ALTER TABLE approvals ADD CONSTRAINT approvals_action_check CHECK (action IN ('create','update','delete','delete_relation','resign_moderator'))");

        // approvals.approvable_type sebelumnya di-morph sebagai UUID (uuidMorphs)
        // tapi User.id adalah integer — perlu allow mixed morph type
        // Solusi: buat approvable_id menjadi text agar bisa store baik UUID maupun integer
        DB::statement('ALTER TABLE approvals ALTER COLUMN approvable_id TYPE text USING approvable_id::text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE approvals DROP CONSTRAINT IF EXISTS approvals_action_check');
        DB::statement("ALTER TABLE approvals ADD CONSTRAINT approvals_action_check CHECK (action IN ('create','update','delete','delete_relation'))");
    }
};
