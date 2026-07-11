<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Camp Management
            ['name' => 'camp.create', 'display_name' => 'Create Camps', 'description' => 'Create new camps', 'group' => 'camps'],
            ['name' => 'camp.view', 'display_name' => 'View Camps', 'description' => 'View camp details', 'group' => 'camps'],
            ['name' => 'camp.update', 'display_name' => 'Update Camps', 'description' => 'Update camp information', 'group' => 'camps'],
            ['name' => 'camp.delete', 'display_name' => 'Delete Camps', 'description' => 'Delete camps', 'group' => 'camps'],
            ['name' => 'camp.manage', 'display_name' => 'Manage Camps', 'description' => 'Toggle camp status', 'group' => 'camps'],
            ['name' => 'camp.view-trash', 'display_name' => 'View Deleted Camps', 'description' => 'View deleted camps in trash', 'group' => 'camps'],
            ['name' => 'camp.restore', 'display_name' => 'Restore Camps', 'description' => 'Restore deleted camps from trash', 'group' => 'camps'],
            ['name' => 'camp.force-delete', 'display_name' => 'Force Delete Camps', 'description' => 'Permanently delete camps', 'group' => 'camps'],

            // Guardian Management
            ['name' => 'guardian.create', 'display_name' => 'Create Guardians', 'description' => 'Register new guardians', 'group' => 'guardians'],
            ['name' => 'guardian.view', 'display_name' => 'View Guardians', 'description' => 'View guardian details', 'group' => 'guardians'],
            ['name' => 'guardian.update', 'display_name' => 'Update Guardians', 'description' => 'Update guardian information', 'group' => 'guardians'],
            ['name' => 'guardian.delete', 'display_name' => 'Delete Guardians', 'description' => 'Delete guardian records', 'group' => 'guardians'],
            ['name' => 'guardian.view-trash', 'display_name' => 'View Deleted Guardians', 'description' => 'View deleted guardians in trash', 'group' => 'guardians'],
            ['name' => 'guardian.restore', 'display_name' => 'Restore Guardians', 'description' => 'Restore deleted guardians from trash', 'group' => 'guardians'],
            ['name' => 'guardian.force-delete', 'display_name' => 'Force Delete Guardians', 'description' => 'Permanently delete guardians', 'group' => 'guardians'],

            // Family Member Management
            ['name' => 'family_member.create', 'display_name' => 'Add Family Members', 'description' => 'Add family members', 'group' => 'family_members'],
            ['name' => 'family_member.view', 'display_name' => 'View Family Members', 'description' => 'View family member details', 'group' => 'family_members'],
            ['name' => 'family_member.update', 'display_name' => 'Update Family Members', 'description' => 'Update family member information', 'group' => 'family_members'],
            ['name' => 'family_member.delete', 'display_name' => 'Delete Family Members', 'description' => 'Delete family member records', 'group' => 'family_members'],
            ['name' => 'family_member.view-trash', 'display_name' => 'View Deleted Family Members', 'description' => 'View deleted family members in trash', 'group' => 'family_members'],
            ['name' => 'family_member.restore', 'display_name' => 'Restore Family Members', 'description' => 'Restore deleted family members from trash', 'group' => 'family_members'],
            ['name' => 'family_member.force-delete', 'display_name' => 'Force Delete Family Members', 'description' => 'Permanently delete family members', 'group' => 'family_members'],

            // Statistics & Reports
            ['name' => 'statistics.view', 'display_name' => 'View Statistics', 'description' => 'View camp statistics', 'group' => 'statistics'],
            ['name' => 'statistics.export', 'display_name' => 'Export Statistics', 'description' => 'Export statistical reports', 'group' => 'statistics'],

            // User Management
            ['name' => 'user.create', 'display_name' => 'Create Users', 'description' => 'Create new users', 'group' => 'users'],
            ['name' => 'user.view', 'display_name' => 'View Users', 'description' => 'View user details', 'group' => 'users'],
            ['name' => 'user.update', 'display_name' => 'Update Users', 'description' => 'Update user information', 'group' => 'users'],
            ['name' => 'user.delete', 'display_name' => 'Delete Users', 'description' => 'Delete user accounts', 'group' => 'users'],
            ['name' => 'user.view-trash', 'display_name' => 'View Deleted Users', 'description' => 'View deleted users in trash', 'group' => 'users'],
            ['name' => 'user.restore', 'display_name' => 'Restore Users', 'description' => 'Restore deleted users from trash', 'group' => 'users'],
            ['name' => 'user.force-delete', 'display_name' => 'Force Delete Users', 'description' => 'Permanently delete users', 'group' => 'users'],

            // Role Management
            ['name' => 'role.manage', 'display_name' => 'Manage Roles', 'description' => 'Manage roles and permissions', 'group' => 'roles'],

            // Aid Distribution Management
            ['name' => 'aid.create', 'display_name' => 'Create Aid Distributions', 'description' => 'Create new aid distributions', 'group' => 'aid_distributions'],
            ['name' => 'aid.view', 'display_name' => 'View Aid Distributions', 'description' => 'View aid distribution details', 'group' => 'aid_distributions'],
            ['name' => 'aid.update', 'display_name' => 'Update Aid Distributions', 'description' => 'Update aid distribution information', 'group' => 'aid_distributions'],
            ['name' => 'aid.delete', 'display_name' => 'Delete Aid Distributions', 'description' => 'Delete aid distributions', 'group' => 'aid_distributions'],
            ['name' => 'aid.distribute', 'display_name' => 'Distribute Aid', 'description' => 'Mark aid as distributed to families', 'group' => 'aid_distributions'],
            ['name' => 'aid.export', 'display_name' => 'Export Aid Reports', 'description' => 'Export aid distribution reports', 'group' => 'aid_distributions'],
            ['name' => 'aid.view-trash', 'display_name' => 'View Deleted Aid Distributions', 'description' => 'View deleted aid distributions in trash', 'group' => 'aid_distributions'],
            ['name' => 'aid.restore', 'display_name' => 'Restore Aid Distributions', 'description' => 'Restore deleted aid distributions from trash', 'group' => 'aid_distributions'],
            ['name' => 'aid.force-delete', 'display_name' => 'Force Delete Aid Distributions', 'description' => 'Permanently delete aid distributions', 'group' => 'aid_distributions'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();

        if ($adminRole) {
            // Admin gets all permissions
            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
        }

        if ($supervisorRole) {
            // Supervisor gets permissions for their assigned camp
            $supervisorPermissions = Permission::whereIn('name', [
                'camp.view',
                'camp.update',
                'camp.manage',
                'guardian.create',
                'guardian.view',
                'guardian.update',
                'guardian.delete',
                'guardian.view-trash',
                'guardian.restore',
                'family_member.create',
                'family_member.view',
                'family_member.update',
                'family_member.delete',
                'family_member.view-trash',
                'family_member.restore',
                'statistics.view',
                'statistics.export',
                'aid.view',
                'aid.distribute',
            ])->get();

            $supervisorRole->permissions()->sync($supervisorPermissions->pluck('id'));
        }
    }
}
