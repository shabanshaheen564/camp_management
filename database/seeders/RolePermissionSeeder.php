<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->first();
        $supervisor = Role::where('name', 'supervisor')->first();
        $manager = Role::where('name', 'camp_manager')->first();

        if ($admin) {
            $admin->permissions()->sync(Permission::all()->pluck('id'));
        }

        if ($supervisor) {
            $supervisor->permissions()->sync(Permission::all()->pluck('id'));
        }

        if ($manager) {
            $managerPermissions = Permission::whereIn('group', [
                'camps', 'guardians', 'family_members', 'statistics', 'aid_distributions', 'maps', 'reports'
            ])->get()->pluck('id');
            $manager->permissions()->sync($managerPermissions);
        }
    }
}
