<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_people',
            'create_person',
            'edit_person',
            'delete_person',
            'view_approvals',
            'approve_changes',
            'reject_changes',
            'manage_moderators',
            'view_audit_logs',
            'export_data',
            'view_family_unit',
            'edit_family_unit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin: semua permission
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Moderator: operasional — BISA ajukan perubahan (create/edit),
        // TIDAK BISA approve/reject. Approve/reject cuma hak admin.
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorRole->syncPermissions([
            'view_people',
            'create_person',
            'edit_person',
            'view_approvals',
            'view_family_unit',
        ]);

        // User: hanya view + edit data milik sendiri (via approval queue)
        // TIDAK bisa create data baru
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'view_people',
            'view_family_unit',
            'edit_person',   // bisa ajukan edit data miliknya (masuk approval)
        ]);
    }
}
