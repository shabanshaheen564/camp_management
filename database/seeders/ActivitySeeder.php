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
        $admin = User::where('email', 'admin@camp.org')->first();
        $supervisor = User::where('email', 'ahmed.supervisor@camp.org')->first();
        $camps = Camp::all();
        $guardians = Guardian::all();

        if (!$admin) {
            $this->command->warn('Admin user not found. Run UserSeeder first.');
            return;
        }

        $campAmal = $camps->firstWhere('name', 'مخيم الأمل - الرمال الغربي') ?? $camps->first();
        $campNour = $camps->firstWhere('name', 'مخيم النور - النصر') ?? $camps->first();
        $campRajaa = $camps->firstWhere('name', 'مخيم الرجاء - الشاطئ') ?? $camps->first();

        $guardianHalabi = $guardians->firstWhere('card_id', '900100001') ?? $guardians->first();
        $guardianAbuAli = $guardians->firstWhere('card_id', '900200001') ?? $guardians->first();

        $activities = [
            [
                'type' => Activity::TYPE_CAMP_CREATED,
                'title' => 'تم إنشاء مخيم جديد: مخيم الأمل - الرمال الغربي',
                'description' => 'مخيم جديد في حي الرمال الغربي بغزة بسعة 850 شخص',
                'icon' => 'building',
                'color' => 'success',
                'user_id' => $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $campAmal?->id,
                'properties' => ['capacity' => 850, 'location' => 'حي الرمال الغربي، غرب مدينة غزة'],
                'created_at' => now()->subMinutes(15)
            ],
            [
                'type' => Activity::TYPE_GUARDIAN_REGISTERED,
                'title' => 'تم تسجيل نازح جديد: محمد أحمد الحلبي',
                'description' => 'تم تسجيل عائلة محمد أحمد الحلبي في مخيم الأمل - الرمال الغربي',
                'icon' => 'user-plus',
                'color' => 'success',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Guardian::class,
                'subject_id' => $guardianHalabi?->id,
                'properties' => ['camp_name' => 'مخيم الأمل - الرمال الغربي', 'family_size' => 4],
                'created_at' => now()->subMinutes(45)
            ],
            [
                'type' => Activity::TYPE_CAMP_UPDATED,
                'title' => 'تم تحديث بيانات مخيم النور - النصر',
                'description' => 'تم تحديث معلومات السعة والخدمات المتاحة',
                'icon' => 'edit',
                'color' => 'primary',
                'user_id' => $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $campNour?->id,
                'properties' => ['updated_fields' => ['capacity', 'services']],
                'created_at' => now()->subHours(1)->subMinutes(20)
            ],
            [
                'type' => Activity::TYPE_FAMILY_MEMBER_ADDED,
                'title' => 'تم إضافة فرد جديد للعائلة',
                'description' => 'تم إضافة طفل جديد لعائلة زياد ناصر أبو علي',
                'icon' => 'users',
                'color' => 'info',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Guardian::class,
                'subject_id' => $guardianAbuAli?->id,
                'properties' => ['family_member_name' => 'سيف زياد أبو علي', 'age' => 6],
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
                'title' => 'تم تغيير حالة مخيم الرجاء - الشاطئ',
                'description' => 'تم تغيير حالة المخيم إلى ممتلئ بسبب ارتفاع نسبة الإشغال',
                'icon' => 'exclamation-triangle',
                'color' => 'warning',
                'user_id' => $supervisor->id ?? $admin->id,
                'subject_type' => Camp::class,
                'subject_id' => $campRajaa?->id,
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
