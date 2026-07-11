<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\Guardian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $camps = Camp::all();
        
        if ($camps->isEmpty()) {
            $this->command->warn('No camps found. Please run CampSeeder first.');
            return;
        }

        $guardians = [
            // Camp 1 - مخيم الأمل
            [
                'camp_id' => $camps->where('name', 'مخيم الأمل')->first()?->id ?? $camps->first()->id,
                'first_name' => 'محمد',
                'second_name' => 'أحمد',
                'third_name' => 'عبدالله',
                'family_name' => 'السلمان',
                'date_of_birth' => '1985-03-15',
                'gender' => 'male',
                'card_id' => '123456789',
                'marital_status' => 'married',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            [
                'camp_id' => $camps->where('name', 'مخيم الأمل')->first()?->id ?? $camps->first()->id,
                'first_name' => 'فاطمة',
                'second_name' => 'خالد',
                'third_name' => 'محمود',
                'family_name' => 'أبو زيد',
                'date_of_birth' => '1990-07-22',
                'gender' => 'female',
                'card_id' => '987654321',
                'marital_status' => 'married',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            [
                'camp_id' => $camps->where('name', 'مخيم الأمل')->first()?->id ?? $camps->first()->id,
                'first_name' => 'علي',
                'second_name' => 'حسن',
                'third_name' => 'محمد',
                'family_name' => 'الأحمد',
                'date_of_birth' => '1975-12-10',
                'gender' => 'male',
                'card_id' => '456789123',
                'marital_status' => 'widowed',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => true,
            ],
            
            // Camp 2 - مخيم النور
            [
                'camp_id' => $camps->where('name', 'مخيم النور')->first()?->id ?? $camps->skip(1)->first()?->id ?? $camps->first()->id,
                'first_name' => 'عائشة',
                'second_name' => 'يوسف',
                'third_name' => 'إبراهيم',
                'family_name' => 'النجار',
                'date_of_birth' => '1988-05-18',
                'gender' => 'female',
                'card_id' => '789123456',
                'marital_status' => 'married',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            [
                'camp_id' => $camps->where('name', 'مخيم النور')->first()?->id ?? $camps->skip(1)->first()?->id ?? $camps->first()->id,
                'first_name' => 'خالد',
                'second_name' => 'عمر',
                'third_name' => 'أحمد',
                'family_name' => 'الحسيني',
                'date_of_birth' => '1982-11-30',
                'gender' => 'male',
                'card_id' => '321654987',
                'marital_status' => 'married',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            
            // Camp 3 - مخيم السلام
            [
                'camp_id' => $camps->where('name', 'مخيم السلام')->first()?->id ?? $camps->skip(2)->first()?->id ?? $camps->first()->id,
                'first_name' => 'مريم',
                'second_name' => 'صالح',
                'third_name' => 'عبدالرحمن',
                'family_name' => 'القاسم',
                'date_of_birth' => '1992-09-08',
                'gender' => 'female',
                'card_id' => '654987321',
                'marital_status' => 'single',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            [
                'camp_id' => $camps->where('name', 'مخيم السلام')->first()?->id ?? $camps->skip(2)->first()?->id ?? $camps->first()->id,
                'first_name' => 'أبو',
                'second_name' => 'محمد',
                'third_name' => 'علي',
                'family_name' => 'الشامي',
                'date_of_birth' => '1965-04-25',
                'gender' => 'male',
                'card_id' => '147258369',
                'marital_status' => 'married',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => false,
            ],
            [
                'camp_id' => $camps->where('name', 'مخيم السلام')->first()?->id ?? $camps->skip(2)->first()?->id ?? $camps->first()->id,
                'first_name' => 'زينب',
                'second_name' => 'طارق',
                'third_name' => 'حسام',
                'family_name' => 'العطار',
                'date_of_birth' => '1995-01-12',
                'gender' => 'female',
                'card_id' => '963852741',
                'marital_status' => 'divorced',
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => true,
            ]
        ];

        foreach ($guardians as $guardianData) {
            Guardian::updateOrCreate(
                ['card_id' => $guardianData['card_id']],
                $guardianData
            );
        }

        $this->command->info('Guardian seeder completed successfully!');
    }
}