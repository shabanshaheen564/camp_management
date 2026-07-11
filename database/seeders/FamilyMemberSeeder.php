<?php

namespace Database\Seeders;

use App\Models\FamilyMember;
use App\Models\Guardian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FamilyMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardians = Guardian::all();
        
        if ($guardians->isEmpty()) {
            $this->command->warn('No guardians found. Please run GuardianSeeder first.');
            return;
        }

        // Get specific guardians by card_id for consistent seeding
        $guardian1 = $guardians->where('card_id', '123456789')->first(); // محمد السلمان
        $guardian2 = $guardians->where('card_id', '987654321')->first(); // فاطمة أبو زيد
        $guardian3 = $guardians->where('card_id', '456789123')->first(); // علي الأحمد
        $guardian4 = $guardians->where('card_id', '789123456')->first(); // عائشة النجار
        $guardian5 = $guardians->where('card_id', '321654987')->first(); // خالد الحسيني
        $guardian6 = $guardians->where('card_id', '147258369')->first(); // أبو محمد الشامي
        $guardian7 = $guardians->where('card_id', '963852741')->first(); // زينب العطار

        $familyMembers = [];

        // Family members for محمد السلمان (married)
        if ($guardian1) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian1->id,
                    'name' => 'أحمد محمد السلمان',
                    'gender' => 'male',
                    'card_id' => '123456790',
                    'date_of_birth' => '2010-06-15',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian1->id,
                    'name' => 'سارة محمد السلمان',
                    'gender' => 'female',
                    'card_id' => '123456791',
                    'date_of_birth' => '2012-09-20',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian1->id,
                    'name' => 'عائشة محمد السلمان',
                    'gender' => 'female',
                    'card_id' => '123456792',
                    'date_of_birth' => '1987-02-10',
                    'nationality' => 'فلسطيني',
                    'phone_number' => '+970123456790',
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for فاطمة أبو زيد (married)
        if ($guardian2) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian2->id,
                    'name' => 'عمر فاطمة أبو زيد',
                    'gender' => 'male',
                    'card_id' => '987654322',
                    'date_of_birth' => '2015-03-12',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian2->id,
                    'name' => 'ليلى فاطمة أبو زيد',
                    'gender' => 'female',
                    'card_id' => '987654323',
                    'date_of_birth' => '2018-11-05',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for علي الأحمد (widowed, has children)
        if ($guardian3) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian3->id,
                    'name' => 'يوسف علي الأحمد',
                    'gender' => 'male',
                    'card_id' => '456789124',
                    'date_of_birth' => '2005-08-14',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian3->id,
                    'name' => 'نور علي الأحمد',
                    'gender' => 'female',
                    'card_id' => '456789125',
                    'date_of_birth' => '2008-12-22',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => true,
                ],
                [
                    'guardian_id' => $guardian3->id,
                    'name' => 'حسام علي الأحمد',
                    'gender' => 'male',
                    'card_id' => '456789126',
                    'date_of_birth' => '2013-04-18',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for عائشة النجار (married)
        if ($guardian4) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian4->id,
                    'name' => 'محمد عائشة النجار',
                    'gender' => 'male',
                    'card_id' => '789123457',
                    'date_of_birth' => '2014-07-30',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for خالد الحسيني (married, large family)
        if ($guardian5) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian5->id,
                    'name' => 'فاطمة خالد الحسيني',
                    'gender' => 'female',
                    'card_id' => '321654988',
                    'date_of_birth' => '2009-01-15',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian5->id,
                    'name' => 'عبدالله خالد الحسيني',
                    'gender' => 'male',
                    'card_id' => '321654989',
                    'date_of_birth' => '2011-05-20',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian5->id,
                    'name' => 'زينب خالد الحسيني',
                    'gender' => 'female',
                    'card_id' => '321654990',
                    'date_of_birth' => '2016-10-08',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian5->id,
                    'name' => 'حسن خالد الحسيني',
                    'gender' => 'male',
                    'card_id' => '321654991',
                    'date_of_birth' => '2019-12-03',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for أبو محمد الشامي (married, elderly with adult children)
        if ($guardian6) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian6->id,
                    'name' => 'محمد أبو الشامي',
                    'gender' => 'male',
                    'card_id' => '147258370',
                    'date_of_birth' => '1995-03-22',
                    'nationality' => 'فلسطيني',
                    'phone_number' => '+970147258370',
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian6->id,
                    'name' => 'أم محمد الشامي',
                    'gender' => 'female',
                    'card_id' => '147258371',
                    'date_of_birth' => '1968-07-14',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Family members for زينب العطار (divorced, single mother)
        if ($guardian7) {
            $familyMembers = array_merge($familyMembers, [
                [
                    'guardian_id' => $guardian7->id,
                    'name' => 'ليث زينب العطار',
                    'gender' => 'male',
                    'card_id' => '963852742',
                    'date_of_birth' => '2017-06-11',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
                [
                    'guardian_id' => $guardian7->id,
                    'name' => 'رنا زينب العطار',
                    'gender' => 'female',
                    'card_id' => '963852743',
                    'date_of_birth' => '2020-02-28',
                    'nationality' => 'فلسطيني',
                    'phone_number' => null,
                    'is_disabled' => false,
                ],
            ]);
        }

        // Create family members
        foreach ($familyMembers as $memberData) {
            FamilyMember::updateOrCreate(
                ['card_id' => $memberData['card_id']],
                $memberData
            );
        }

        $this->command->info('FamilyMember seeder completed successfully!');
        $this->command->info('Created ' . count($familyMembers) . ' family members.');
    }
}