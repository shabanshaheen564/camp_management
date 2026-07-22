<?php

namespace Database\Seeders;

use App\Models\FamilyMember;
use App\Models\Guardian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FamilyMemberSeeder extends Seeder
{
    /**
     * أفراد كل عائلة (زوج/زوجة + أبناء) مبنيين على card_id ولي الأمر.
     * الأعمار محسوبة بشكل منطقي متوافق مع عمر وحالة كل ولي أمر:
     * المتزوجون عندهم زوج/زوجة + أطفال، الأرامل والمطلقون عندهم أطفال بدون
     * زوج/زوجة، الأعزب بدون أفراد.
     */
    public function run(): void
    {
        $guardians = Guardian::all();

        if ($guardians->isEmpty()) {
            $this->command->warn('No guardians found. Please run GuardianSeeder first.');
            return;
        }

        // key = card_id تبع ولي الأمر
        $familyData = [

            // ---- مخيم الأمل ----
            '900100001' => [ // محمد أحمد الحلبي - متزوج
                ['name' => 'سلوى محمد الحلبي', 'gender' => 'female', 'card_id' => '900100011', 'dob' => '1985-08-20', 'phone' => '+970599100011', 'disabled' => false],
                ['name' => 'ياسين محمد الحلبي', 'gender' => 'male', 'card_id' => '900100012', 'dob' => '2010-01-15', 'phone' => null, 'disabled' => false],
                ['name' => 'رهف محمد الحلبي', 'gender' => 'female', 'card_id' => '900100013', 'dob' => '2014-05-09', 'phone' => null, 'disabled' => false],
            ],
            '900100002' => [ // سلمى يوسف البرغوثي - أرملة
                ['name' => 'عمر سلمى البرغوثي', 'gender' => 'male', 'card_id' => '900100021', 'dob' => '2008-02-11', 'phone' => null, 'disabled' => false],
                ['name' => 'جود سلمى البرغوثي', 'gender' => 'female', 'card_id' => '900100022', 'dob' => '2012-09-27', 'phone' => null, 'disabled' => false],
            ],
            '900100003' => [ // خليل إبراهيم شحادة - مطلق
                ['name' => 'آدم خليل شحادة', 'gender' => 'male', 'card_id' => '900100031', 'dob' => '2015-03-03', 'phone' => null, 'disabled' => false],
            ],
            '900100004' => [], // عبير سامي مقداد - عزباء، بدون أفراد

            // ---- مخيم الكرامة ----
            '900200001' => [ // زياد ناصر أبو علي - متزوج، عائلة كبيرة
                ['name' => 'هدى زياد أبو علي', 'gender' => 'female', 'card_id' => '900200011', 'dob' => '1988-12-01', 'phone' => '+970599200011', 'disabled' => false],
                ['name' => 'كريم زياد أبو علي', 'gender' => 'male', 'card_id' => '900200012', 'dob' => '2011-06-18', 'phone' => null, 'disabled' => false],
                ['name' => 'لارا زياد أبو علي', 'gender' => 'female', 'card_id' => '900200013', 'dob' => '2016-10-25', 'phone' => null, 'disabled' => false],
                ['name' => 'سيف زياد أبو علي', 'gender' => 'male', 'card_id' => '900200014', 'dob' => '2020-02-14', 'phone' => null, 'disabled' => false],
            ],
            '900200002' => [ // منى خالد الديك - متزوجة
                ['name' => 'نادين منى الديك', 'gender' => 'female', 'card_id' => '900200021', 'dob' => '2012-04-30', 'phone' => null, 'disabled' => false],
                ['name' => 'وسيم منى الديك', 'gender' => 'male', 'card_id' => '900200022', 'dob' => '2017-11-05', 'phone' => null, 'disabled' => false],
            ],
            '900200003' => [ // أسامة فتحي ريان - أرمل، من ذوي الإعاقة
                ['name' => 'مصطفى أسامة ريان', 'gender' => 'male', 'card_id' => '900200031', 'dob' => '2006-08-19', 'phone' => null, 'disabled' => false],
                ['name' => 'دانا أسامة ريان', 'gender' => 'female', 'card_id' => '900200032', 'dob' => '2009-12-22', 'phone' => null, 'disabled' => true],
            ],
            '900200004' => [ // هناء عماد صيام - مطلقة
                ['name' => 'إيلاف هناء صيام', 'gender' => 'female', 'card_id' => '900200041', 'dob' => '2018-07-07', 'phone' => null, 'disabled' => false],
            ],

            // ---- مخيم الصمود ----
            '900300001' => [ // باسل مروان قديح - متزوج، أكبر عائلة
                ['name' => 'رشا باسل قديح', 'gender' => 'female', 'card_id' => '900300011', 'dob' => '1984-03-17', 'phone' => '+970599300011', 'disabled' => false],
                ['name' => 'محمود باسل قديح', 'gender' => 'male', 'card_id' => '900300012', 'dob' => '2007-09-23', 'phone' => null, 'disabled' => false],
                ['name' => 'سجى باسل قديح', 'gender' => 'female', 'card_id' => '900300013', 'dob' => '2010-01-30', 'phone' => null, 'disabled' => false],
                ['name' => 'يزن باسل قديح', 'gender' => 'male', 'card_id' => '900300014', 'dob' => '2013-06-06', 'phone' => null, 'disabled' => false],
                ['name' => 'ملك باسل قديح', 'gender' => 'female', 'card_id' => '900300015', 'dob' => '2019-04-11', 'phone' => null, 'disabled' => false],
            ],
            '900300002' => [ // ريم توفيق العطار - متزوجة
                ['name' => 'جنى ريم العطار', 'gender' => 'female', 'card_id' => '900300021', 'dob' => '2015-08-08', 'phone' => null, 'disabled' => false],
            ],
            '900300003' => [], // عادل حسين أبو زايدة - أعزب، بدون أفراد
            '900300004' => [ // وفاء جمال الحداد - أرملة، من ذوي الإعاقة
                ['name' => 'إسلام وفاء الحداد', 'gender' => 'male', 'card_id' => '900300041', 'dob' => '2001-02-02', 'phone' => '+970599300041', 'disabled' => false],
            ],

            // ---- مخيم النور ----
            '900400001' => [ // كريم صلاح النخالة - متزوج
                ['name' => 'أسماء كريم النخالة', 'gender' => 'female', 'card_id' => '900400011', 'dob' => '1987-06-14', 'phone' => '+970599400011', 'disabled' => false],
                ['name' => 'زين كريم النخالة', 'gender' => 'male', 'card_id' => '900400012', 'dob' => '2013-10-02', 'phone' => null, 'disabled' => false],
                ['name' => 'لمى كريم النخالة', 'gender' => 'female', 'card_id' => '900400013', 'dob' => '2017-01-19', 'phone' => null, 'disabled' => false],
            ],
            '900400002' => [ // لينا وليد أبو معيلق - متزوجة
                ['name' => 'تيم لينا أبو معيلق', 'gender' => 'male', 'card_id' => '900400021', 'dob' => '2019-03-27', 'phone' => null, 'disabled' => false],
                ['name' => 'جنات لينا أبو معيلق', 'gender' => 'female', 'card_id' => '900400022', 'dob' => '2021-12-15', 'phone' => null, 'disabled' => false],
            ],
            '900400003' => [ // طارق رياض السقا - مطلق
                ['name' => 'أنس طارق السقا', 'gender' => 'male', 'card_id' => '900400031', 'dob' => '2014-05-05', 'phone' => null, 'disabled' => true],
            ],
            '900400004' => [], // أمل فؤاد بركة - عزباء، بدون أفراد

            // ---- مخيم الرجاء ----
            '900500001' => [ // نائل عصام الهور - متزوج
                ['name' => 'سميرة نائل الهور', 'gender' => 'female', 'card_id' => '900500011', 'dob' => '1982-11-23', 'phone' => '+970599500011', 'disabled' => false],
                ['name' => 'علاء نائل الهور', 'gender' => 'male', 'card_id' => '900500012', 'dob' => '2005-09-12', 'phone' => null, 'disabled' => false],
                ['name' => 'رغد نائل الهور', 'gender' => 'female', 'card_id' => '900500013', 'dob' => '2009-02-28', 'phone' => null, 'disabled' => false],
                ['name' => 'حمزة نائل الهور', 'gender' => 'male', 'card_id' => '900500014', 'dob' => '2014-07-19', 'phone' => null, 'disabled' => false],
            ],
            '900500002' => [ // دعاء إياد الأسطل - أرملة، من ذوي الإعاقة، أبناء بالغون
                ['name' => 'إياد دعاء الأسطل', 'gender' => 'male', 'card_id' => '900500021', 'dob' => '1990-05-16', 'phone' => '+970599500021', 'disabled' => false],
                ['name' => 'سهى دعاء الأسطل', 'gender' => 'female', 'card_id' => '900500022', 'dob' => '1993-08-08', 'phone' => '+970599500022', 'disabled' => false],
            ],
            '900500003' => [], // فادي رامي شبير - أعزب، بدون أفراد
            '900500004' => [ // سناء محمود اللوح - متزوجة
                ['name' => 'رهام سناء اللوح', 'gender' => 'female', 'card_id' => '900500041', 'dob' => '2012-09-09', 'phone' => null, 'disabled' => false],
                ['name' => 'عدي سناء اللوح', 'gender' => 'male', 'card_id' => '900500042', 'dob' => '2016-12-12', 'phone' => null, 'disabled' => false],
                ['name' => 'نغم سناء اللوح', 'gender' => 'female', 'card_id' => '900500043', 'dob' => '2021-04-04', 'phone' => null, 'disabled' => false],
            ],
        ];

        $createdCount = 0;

        foreach ($guardians as $guardian) {
            if (!isset($familyData[$guardian->card_id])) {
                continue;
            }

            foreach ($familyData[$guardian->card_id] as $member) {
                FamilyMember::updateOrCreate(
                    ['card_id' => $member['card_id']],
                    [
                        'guardian_id'   => $guardian->id,
                        'name'          => $member['name'],
                        'gender'        => $member['gender'],
                        'card_id'       => $member['card_id'],
                        'date_of_birth' => $member['dob'],
                        'nationality'   => 'فلسطيني',
                        'phone_number'  => $member['phone'],
                        'is_disabled'   => $member['disabled'],
                    ]
                );
                $createdCount++;
            }
        }

        $this->command->info('FamilyMember seeder completed successfully!');
        $this->command->info('Created/updated ' . $createdCount . ' family members.');
    }
}
