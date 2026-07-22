<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampSeeder extends Seeder
{
    /**
     * 5 مخيمات نزوح موزعة في غرب مدينة غزة (الرمال الغربي، الشيخ عجلين،
     * تل الهوا، النصر، الشاطئ) بإحداثيات تقريبية واقعية لكل منطقة.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@camp.org')->first();

        $camps = [
            [
                'name' => 'مخيم الأمل - الرمال الغربي',
                'location' => 'حي الرمال الغربي، غرب مدينة غزة',
                'latitude' => 31.5085,
                'longitude' => 34.4437,
                'capacity' => 850,
                'current_occupancy' => 0,
                'manager' => 'سامي عودة',
                'phone' => '+970599100000',
                'description' => 'مخيم إيواء للنازحين يقدم خدمات الإسكان والغذاء والرعاية الصحية الأولية',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم الكرامة - الشيخ عجلين',
                'location' => 'حي الشيخ عجلين، غرب مدينة غزة',
                'latitude' => 31.4928,
                'longitude' => 34.4462,
                'capacity' => 620,
                'current_occupancy' => 0,
                'manager' => 'رنا مطر',
                'phone' => '+970599200000',
                'description' => 'مخيم متوسط الحجم يخدم العائلات النازحة من الأحياء المجاورة',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم الصمود - تل الهوا',
                'location' => 'حي تل الهوا، غرب مدينة غزة',
                'latitude' => 31.4957,
                'longitude' => 34.4534,
                'capacity' => 900,
                'current_occupancy' => 0,
                'manager' => 'خالد أبو شمالة',
                'phone' => '+970599300000',
                'description' => 'أكبر مخيمات المنطقة، يضم مركزاً طبياً ومرافق تعليمية مؤقتة',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم النور - النصر',
                'location' => 'حي النصر، غرب مدينة غزة',
                'latitude' => 31.5142,
                'longitude' => 34.4459,
                'capacity' => 750,
                'current_occupancy' => 0,
                'manager' => 'نور حمدان',
                'phone' => '+970599400000',
                'description' => 'مخيم يستوعب عائلات نازحة حديثاً مع أولوية لذوي الاحتياجات الخاصة',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم الرجاء - الشاطئ',
                'location' => 'منطقة الشاطئ، غرب مدينة غزة',
                'latitude' => 31.5257,
                'longitude' => 34.4383,
                'capacity' => 1100,
                'current_occupancy' => 0,
                'manager' => 'عمر ياسين',
                'phone' => '+970599500000',
                'description' => 'أكبر المخيمات من حيث السعة الاستيعابية، قريب من الساحل',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
        ];

        foreach ($camps as $campData) {
            Camp::updateOrCreate(
                ['name' => $campData['name']],
                $campData
            );
        }

        $this->assignStaffToCamps();
    }

    /**
     * ربط كل مشرف (supervisor) ومدير مخيم (camp_manager) بمخيمه المخصص له
     * بعد إنشاء المخيمات (لازم تشتغل هاي الدالة بعد ما تكون المخيمات موجودة).
     */
    private function assignStaffToCamps(): void
    {
        $assignments = [
            'مخيم الأمل - الرمال الغربي'   => ['ahmed.supervisor@camp.org', 'sami.manager@camp.org'],
            'مخيم الكرامة - الشيخ عجلين'    => ['fatema.supervisor@camp.org', 'rana.manager@camp.org'],
            'مخيم الصمود - تل الهوا'        => ['mahmoud.supervisor@camp.org', 'khaled.manager@camp.org'],
            'مخيم النور - النصر'            => ['heba.supervisor@camp.org', 'noor.manager@camp.org'],
            'مخيم الرجاء - الشاطئ'          => ['yousef.supervisor@camp.org', 'omar.manager@camp.org'],
        ];

        foreach ($assignments as $campName => $emails) {
            $camp = Camp::where('name', $campName)->first();
            if (!$camp) {
                continue;
            }

            foreach ($emails as $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update(['camp_id' => $camp->id]);
                }
            }
        }
    }
}
