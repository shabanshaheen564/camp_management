<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access and management capabilities',
                'is_active' => true,
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Camp Supervisor',
                'description' => 'Manages specific camp and its displaced people',
                'is_active' => true,
            ],
            [
                'name' => 'camp_manager',
                'display_name' => 'Camp Manager',
                'description' => 'Manages assigned camp operations with limited permissions',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
