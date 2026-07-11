<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\User;
use App\Models\Camp;
use App\Models\Guardian;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample users and camps
        $admin = User::where('email', 'admin@camp.org')->first();
        $supervisor = User::where('email', 'ahmed.supervisor@camp.org')->first();
        $camps = Camp::all();
        $guardians = Guardian::all();

        if (!$admin) {
            $this->command->warn('Admin user not found. Run UserSeeder first.');
            return;
        }

        // Sample activities for the last few days
        $activities = [
            [
                'type' => Activity::TYPE_CAMP_CREATED,
                'title' => 'تم إنشاء مخيم جديد: مخيم الأمل',
                'description' => 'مخيم جديد في قطاع غزة بسعة 500 شخص',
                'icon' => 'building',
                'color' => 'success',
                'user_id' => $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $camps->first()->id ?? null,
                'properties' => ['capacity' => 500, 'location' => 'قطاع غزة'],
                'created_at' => now()->subMinutes(15)
            ],
            [
                'type' => Activity::TYPE_GUARDIAN_REGISTERED,
                'title' => 'تم تسجيل نازح جديد: أحمد محمد السالم',
                'description' => 'تم تسجيل أحمد محمد السالم في مخيم السلام',
                'icon' => 'user-plus',
                'color' => 'success',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Guardian::class,
                'subject_id' => $guardians->first()->id ?? null,
                'properties' => ['camp_name' => 'مخيم السلام', 'family_size' => 4],
                'created_at' => now()->subMinutes(45)
            ],
            [
                'type' => Activity::TYPE_CAMP_UPDATED,
                'title' => 'تم تحديث بيانات مخيم النور',
                'description' => 'تم تحديث معلومات السعة والخدمات المتاحة',
                'icon' => 'edit',
                'color' => 'primary',
                'user_id' => $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $camps->skip(1)->first()->id ?? $camps->first()->id,
                'properties' => ['updated_fields' => ['capacity', 'services']],
                'created_at' => now()->subHours(1)->subMinutes(20)
            ],
            [
                'type' => Activity::TYPE_FAMILY_MEMBER_ADDED,
                'title' => 'تم إضافة فرد جديد للعائلة',
                'description' => 'تم إضافة طفل جديد لعائلة محمد أحمد',
                'icon' => 'users',
                'color' => 'info',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Guardian::class,
                'subject_id' => $guardians->skip(1)->first()->id ?? $guardians->first()->id,
                'properties' => ['family_member_name' => 'سارة محمد أحمد', 'age' => 8],
                'created_at' => now()->subHours(2)
            ],
            [
                'type' => Activity::TYPE_STATISTICS_EXPORTED,
                'title' => 'تم تصدير تقرير إحصائي',
                'description' => 'تم تصدير التقرير الشهري للإحصائيات بصيغة PDF',
                'icon' => 'file-pdf',
                'color' => 'danger',
                'user_id' => $admin->id,
                'subject_type' => null,
                'subject_id' => null,
                'properties' => ['export_type' => 'pdf', 'report_period' => 'monthly'],
                'created_at' => now()->subHours(4)
            ],
            [
                'type' => Activity::TYPE_USER_CREATED,
                'title' => 'تم إنشاء مستخدم جديد',
                'description' => 'تم إنشاء حساب مستخدم جديد بصلاحية مشرف مخيم',
                'icon' => 'user-cog',
                'color' => 'warning',
                'user_id' => $admin->id,
                'subject_type' => User::class,
                'subject_id' => $supervisor->id ?? null,
                'properties' => ['role' => 'supervisor', 'camp_assigned' => true],
                'created_at' => now()->subHours(6)
            ],
            [
                'type' => Activity::TYPE_CAMP_STATUS_CHANGED,
                'title' => 'تم تغيير حالة مخيم الرجاء',
                'description' => 'تم تغيير حالة المخيم إلى ممتلئ',
                'icon' => 'exclamation-triangle',
                'color' => 'warning',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $camps->last()->id ?? $camps->first()->id,
                'properties' => ['old_status' => 'active', 'new_status' => 'full', 'occupancy_rate' => 95],
                'created_at' => now()->subDay()
            ]
        ];

        foreach ($activities as $activity) {
            Activity::create($activity);
        }

        $this->command->info('Activity seeder completed successfully!');
        $this->command->info('Created ' . count($activities) . ' sample activities.');
    }
}