<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        //- Admin: admin@camp.org / admin123
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@camp.org'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@camp.org',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Sample Supervisor Users
        $supervisorUsers = [
            [
                'name' => 'أحمد محمد',
                'email' => 'ahmed.supervisor@camp.org',
                'password' => Hash::make('supervisor123'),
                'role_id' => $supervisorRole?->id,
                'camp_id' => null, // Will be set after camps are created
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'فاطمة أحمد',
                'email' => 'fatema.supervisor@camp.org',
                'password' => Hash::make('supervisor123'),
                'role_id' => $supervisorRole?->id,
                'camp_id' => null, // Will be set after camps are created
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        ];

        foreach ($supervisorUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
