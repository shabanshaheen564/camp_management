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
     * حساب أدمن واحد، + مشرف (supervisor) ومدير مخيم (camp_manager) لكل مخيم
     * من المخيمات الخمسة. الربط بـ camp_id بيصير لاحقاً من CampSeeder بعد
     * ما يتم إنشاء المخيمات فعلياً.
     */
    public function run(): void
    {
        $adminRole      = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $managerRole    = Role::where('name', 'camp_manager')->first();

        // -------- Admin: admin@camp.org / admin123 --------
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

        // -------- مشرفو المخيمات (Camp Supervisors) --------
        // كلمة السر لكل الحسابات: supervisor123
        $supervisors = [
            ['name' => 'أحمد الحلبي',      'email' => 'ahmed.supervisor@camp.org'],
            ['name' => 'فاطمة أبو دقة',     'email' => 'fatema.supervisor@camp.org'],
            ['name' => 'محمود شقير',        'email' => 'mahmoud.supervisor@camp.org'],
            ['name' => 'هبة الترك',         'email' => 'heba.supervisor@camp.org'],
            ['name' => 'يوسف النجار',       'email' => 'yousef.supervisor@camp.org'],
        ];

        foreach ($supervisors as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'email' => $s['email'],
                    'password' => Hash::make('supervisor123'),
                    'role_id' => $supervisorRole?->id,
                    'camp_id' => null, // بينحط لاحقاً من CampSeeder
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // -------- مديرو المخيمات (Camp Managers) --------
        // كلمة السر لكل الحسابات: manager123
        $managers = [
            ['name' => 'سامي عودة',         'email' => 'sami.manager@camp.org'],
            ['name' => 'رنا مطر',           'email' => 'rana.manager@camp.org'],
            ['name' => 'خالد أبو شمالة',    'email' => 'khaled.manager@camp.org'],
            ['name' => 'نور حمدان',         'email' => 'noor.manager@camp.org'],
            ['name' => 'عمر ياسين',         'email' => 'omar.manager@camp.org'],
        ];

        foreach ($managers as $m) {
            User::updateOrCreate(
                ['email' => $m['email']],
                [
                    'name' => $m['name'],
                    'email' => $m['email'],
                    'password' => Hash::make('manager123'),
                    'role_id' => $managerRole?->id,
                    'camp_id' => null, // بينحط لاحقاً من CampSeeder
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
