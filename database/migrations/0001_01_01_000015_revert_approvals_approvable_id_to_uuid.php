<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus resign_moderator yang pakai text approvable_id
        DB::table('approvals')->where('action', 'resign_moderator')->delete();

        // 2. Kembalikan approvable_id ke uuid (revert dari migration 000015)
        DB::statement('ALTER TABLE approvals ALTER COLUMN approvable_id TYPE uuid USING approvable_id::uuid');

        // 3. Buat approvable_id dan approvable_type nullable
        //    agar resign_moderator bisa disimpan tanpa approvable
        DB::statement('ALTER TABLE approvals ALTER COLUMN approvable_id DROP NOT NULL');
        DB::statement('ALTER TABLE approvals ALTER COLUMN approvable_type DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE approvals ALTER COLUMN approvable_id TYPE text USING approvable_id::text');
    }
};
