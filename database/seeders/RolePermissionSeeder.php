<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            // person management
            'view_people',
            'create_person',
            'edit_person',
            'delete_person',

            // approval workflow
            'view_approvals',
            'approve_changes',
            'reject_changes',

            // admin only
            'manage_moderators',
            'view_audit_logs',
            'export_data',

            // family unit
            'view_family_unit',
            'edit_family_unit',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // create roles and assign permissions

        // admin: all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // moderator: operational permissions
        $moderatorRole = Role::create(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'view_people',
            'create_person',
            'edit_person',
            'view_approvals',
            'approve_changes',
            'reject_changes',
            'view_family_unit',
        ]);

        // user: view-only + request changes
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'view_people',
            'view_family_unit',
        ]);
    }
}
