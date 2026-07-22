<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\Guardian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuardianSeeder extends Seeder
{
    /**
     * 20 عائلة (4 لكل مخيم من المخيمات الخمسة) بحالات اجتماعية وأعمار
     * متنوعة (متزوج/أرمل/مطلق/أعزب) وبعضها من ذوي الإعاقة، لتمثيل واقعي
     * لتركيبة سكانية حقيقية داخل مخيمات النزوح.
     */
    public function run(): void
    {
        $camps = Camp::all();

        if ($camps->isEmpty()) {
            $this->command->warn('No camps found. Please run CampSeeder first.');
            return;
        }

        $campId = fn (string $name) => $camps->where('name', $name)->first()?->id ?? $camps->first()->id;

        $guardians = [
            // ================= مخيم الأمل - الرمال الغربي =================
            [
                'camp_id' => $campId('مخيم الأمل - الرمال الغربي'),
                'first_name' => 'محمد', 'second_name' => 'أحمد', 'third_name' => 'سليم', 'family_name' => 'الحلبي',
                'date_of_birth' => '1982-04-12', 'gender' => 'male', 'card_id' => '900100001',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599100001',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الأمل - الرمال الغربي'),
                'first_name' => 'سلمى', 'second_name' => 'يوسف', 'third_name' => 'كامل', 'family_name' => 'البرغوثي',
                'date_of_birth' => '1978-11-03', 'gender' => 'female', 'card_id' => '900100002',
                'marital_status' => 'widowed', 'nationality' => 'فلسطيني', 'phone' => '+970599100002',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الأمل - الرمال الغربي'),
                'first_name' => 'خليل', 'second_name' => 'إبراهيم', 'third_name' => 'محمود', 'family_name' => 'شحادة',
                'date_of_birth' => '1990-02-20', 'gender' => 'male', 'card_id' => '900100003',
                'marital_status' => 'divorced', 'nationality' => 'فلسطيني', 'phone' => '+970599100003',
                'family_member_number' => 0, 'is_disabled' => true,
            ],
            [
                'camp_id' => $campId('مخيم الأمل - الرمال الغربي'),
                'first_name' => 'عبير', 'second_name' => 'سامي', 'third_name' => 'فؤاد', 'family_name' => 'مقداد',
                'date_of_birth' => '1995-07-15', 'gender' => 'female', 'card_id' => '900100004',
                'marital_status' => 'single', 'nationality' => 'فلسطيني', 'phone' => '+970599100004',
                'family_member_number' => 0, 'is_disabled' => false,
            ],

            // ================= مخيم الكرامة - الشيخ عجلين =================
            [
                'camp_id' => $campId('مخيم الكرامة - الشيخ عجلين'),
                'first_name' => 'زياد', 'second_name' => 'ناصر', 'third_name' => 'محمد', 'family_name' => 'أبو علي',
                'date_of_birth' => '1985-09-09', 'gender' => 'male', 'card_id' => '900200001',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599200001',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الكرامة - الشيخ عجلين'),
                'first_name' => 'منى', 'second_name' => 'خالد', 'third_name' => 'رشيد', 'family_name' => 'الديك',
                'date_of_birth' => '1988-01-25', 'gender' => 'female', 'card_id' => '900200002',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599200002',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الكرامة - الشيخ عجلين'),
                'first_name' => 'أسامة', 'second_name' => 'فتحي', 'third_name' => 'عبدالكريم', 'family_name' => 'ريان',
                'date_of_birth' => '1975-06-30', 'gender' => 'male', 'card_id' => '900200003',
                'marital_status' => 'widowed', 'nationality' => 'فلسطيني', 'phone' => '+970599200003',
                'family_member_number' => 0, 'is_disabled' => true,
            ],
            [
                'camp_id' => $campId('مخيم الكرامة - الشيخ عجلين'),
                'first_name' => 'هناء', 'second_name' => 'عماد', 'third_name' => 'زكي', 'family_name' => 'صيام',
                'date_of_birth' => '1992-03-14', 'gender' => 'female', 'card_id' => '900200004',
                'marital_status' => 'divorced', 'nationality' => 'فلسطيني', 'phone' => '+970599200004',
                'family_member_number' => 0, 'is_disabled' => false,
            ],

            // ================= مخيم الصمود - تل الهوا =================
            [
                'camp_id' => $campId('مخيم الصمود - تل الهوا'),
                'first_name' => 'باسل', 'second_name' => 'مروان', 'third_name' => 'سعيد', 'family_name' => 'قديح',
                'date_of_birth' => '1980-12-01', 'gender' => 'male', 'card_id' => '900300001',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599300001',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الصمود - تل الهوا'),
                'first_name' => 'ريم', 'second_name' => 'توفيق', 'third_name' => 'حمدي', 'family_name' => 'العطار',
                'date_of_birth' => '1991-05-18', 'gender' => 'female', 'card_id' => '900300002',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599300002',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الصمود - تل الهوا'),
                'first_name' => 'عادل', 'second_name' => 'حسين', 'third_name' => 'جميل', 'family_name' => 'أبو زايدة',
                'date_of_birth' => '1998-08-22', 'gender' => 'male', 'card_id' => '900300003',
                'marital_status' => 'single', 'nationality' => 'فلسطيني', 'phone' => '+970599300003',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الصمود - تل الهوا'),
                'first_name' => 'وفاء', 'second_name' => 'جمال', 'third_name' => 'أمين', 'family_name' => 'الحداد',
                'date_of_birth' => '1970-10-10', 'gender' => 'female', 'card_id' => '900300004',
                'marital_status' => 'widowed', 'nationality' => 'فلسطيني', 'phone' => '+970599300004',
                'family_member_number' => 0, 'is_disabled' => true,
            ],

            // ================= مخيم النور - النصر =================
            [
                'camp_id' => $campId('مخيم النور - النصر'),
                'first_name' => 'كريم', 'second_name' => 'صلاح', 'third_name' => 'عبدالله', 'family_name' => 'النخالة',
                'date_of_birth' => '1983-02-28', 'gender' => 'male', 'card_id' => '900400001',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599400001',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم النور - النصر'),
                'first_name' => 'لينا', 'second_name' => 'وليد', 'third_name' => 'فوزي', 'family_name' => 'أبو معيلق',
                'date_of_birth' => '1994-09-05', 'gender' => 'female', 'card_id' => '900400002',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599400002',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم النور - النصر'),
                'first_name' => 'طارق', 'second_name' => 'رياض', 'third_name' => 'نبيل', 'family_name' => 'السقا',
                'date_of_birth' => '1987-11-11', 'gender' => 'male', 'card_id' => '900400003',
                'marital_status' => 'divorced', 'nationality' => 'فلسطيني', 'phone' => '+970599400003',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم النور - النصر'),
                'first_name' => 'أمل', 'second_name' => 'فؤاد', 'third_name' => 'حسن', 'family_name' => 'بركة',
                'date_of_birth' => '1999-04-04', 'gender' => 'female', 'card_id' => '900400004',
                'marital_status' => 'single', 'nationality' => 'فلسطيني', 'phone' => '+970599400004',
                'family_member_number' => 0, 'is_disabled' => false,
            ],

            // ================= مخيم الرجاء - الشاطئ =================
            [
                'camp_id' => $campId('مخيم الرجاء - الشاطئ'),
                'first_name' => 'نائل', 'second_name' => 'عصام', 'third_name' => 'رشاد', 'family_name' => 'الهور',
                'date_of_birth' => '1979-07-07', 'gender' => 'male', 'card_id' => '900500001',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599500001',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الرجاء - الشاطئ'),
                'first_name' => 'دعاء', 'second_name' => 'إياد', 'third_name' => 'شوقي', 'family_name' => 'الأسطل',
                'date_of_birth' => '1965-01-01', 'gender' => 'female', 'card_id' => '900500002',
                'marital_status' => 'widowed', 'nationality' => 'فلسطيني', 'phone' => '+970599500002',
                'family_member_number' => 0, 'is_disabled' => true,
            ],
            [
                'camp_id' => $campId('مخيم الرجاء - الشاطئ'),
                'first_name' => 'فادي', 'second_name' => 'رامي', 'third_name' => 'وجيه', 'family_name' => 'شبير',
                'date_of_birth' => '1996-06-06', 'gender' => 'male', 'card_id' => '900500003',
                'marital_status' => 'single', 'nationality' => 'فلسطيني', 'phone' => '+970599500003',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
            [
                'camp_id' => $campId('مخيم الرجاء - الشاطئ'),
                'first_name' => 'سناء', 'second_name' => 'محمود', 'third_name' => 'إسماعيل', 'family_name' => 'اللوح',
                'date_of_birth' => '1989-03-03', 'gender' => 'female', 'card_id' => '900500004',
                'marital_status' => 'married', 'nationality' => 'فلسطيني', 'phone' => '+970599500004',
                'family_member_number' => 0, 'is_disabled' => false,
            ],
        ];

        foreach ($guardians as $guardianData) {
            Guardian::updateOrCreate(
                ['card_id' => $guardianData['card_id']],
                $guardianData
            );
        }

        $this->command->info('Guardian seeder completed successfully! Created/updated ' . count($guardians) . ' guardians across 5 camps.');
    }
}
