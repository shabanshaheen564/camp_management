<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AidType;

class AidTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aidTypes = [
            // Food items
            [
                'name' => 'حصص غذائية',
                'name_en' => 'Food Packages',
                'description' => 'حصص غذائية أساسية تحتوي على الأرز والعدس والزيت والسكر والشاي',
                'unit' => 'حصة',
                'category' => AidType::CATEGORY_FOOD,
                'icon' => 'utensils',
                'color' => 'success',
            ],
            [
                'name' => 'خبز',
                'name_en' => 'Bread',
                'description' => 'خبز طازج للاستهلاك اليومي',
                'unit' => 'رغيف',
                'category' => AidType::CATEGORY_FOOD,
                'icon' => 'bread-slice',
                'color' => 'warning',
            ],
            [
                'name' => 'حليب الأطفال',
                'name_en' => 'Baby Formula',
                'description' => 'حليب صناعي للرضع والأطفال الصغار',
                'unit' => 'علبة',
                'category' => AidType::CATEGORY_FOOD,
                'icon' => 'baby',
                'color' => 'info',
            ],
            
            // Water
            [
                'name' => 'مياه شرب',
                'name_en' => 'Drinking Water',
                'description' => 'مياه شرب نظيفة وآمنة',
                'unit' => 'لتر',
                'category' => AidType::CATEGORY_WATER,
                'icon' => 'tint',
                'color' => 'primary',
            ],
            [
                'name' => 'خزانات مياه',
                'name_en' => 'Water Tanks',
                'description' => 'خزانات مياه للاستخدام الأسري',
                'unit' => 'خزان',
                'category' => AidType::CATEGORY_WATER,
                'icon' => 'water',
                'color' => 'primary',
            ],

            // Medical
            [
                'name' => 'أدوية أساسية',
                'name_en' => 'Basic Medicines',
                'description' => 'أدوية أساسية للإسعافات الأولية والحالات البسيطة',
                'unit' => 'علبة',
                'category' => AidType::CATEGORY_MEDICAL,
                'icon' => 'pills',
                'color' => 'danger',
            ],
            [
                'name' => 'حقائب طبية',
                'name_en' => 'Medical Kits',
                'description' => 'حقائب إسعاف أولي للعائلات',
                'unit' => 'حقيبة',
                'category' => AidType::CATEGORY_MEDICAL,
                'icon' => 'medkit',
                'color' => 'danger',
            ],

            // Clothing & Shelter
            [
                'name' => 'بطانيات',
                'name_en' => 'Blankets',
                'description' => 'بطانيات دافئة للحماية من البرد',
                'unit' => 'بطانية',
                'category' => AidType::CATEGORY_CLOTHING,
                'icon' => 'bed',
                'color' => 'secondary',
            ],
            [
                'name' => 'ملابس شتوية',
                'name_en' => 'Winter Clothes',
                'description' => 'ملابس شتوية للأطفال والكبار',
                'unit' => 'قطعة',
                'category' => AidType::CATEGORY_CLOTHING,
                'icon' => 'tshirt',
                'color' => 'dark',
            ],
            [
                'name' => 'خيام',
                'name_en' => 'Tents',
                'description' => 'خيام للإيواء المؤقت',
                'unit' => 'خيمة',
                'category' => AidType::CATEGORY_SHELTER,
                'icon' => 'campground',
                'color' => 'success',
            ],

            // Hygiene
            [
                'name' => 'مواد تنظيف',
                'name_en' => 'Cleaning Supplies',
                'description' => 'صابون ومنظفات ومواد التنظيف الأساسية',
                'unit' => 'حزمة',
                'category' => AidType::CATEGORY_HYGIENE,
                'icon' => 'soap',
                'color' => 'info',
            ],
            [
                'name' => 'فوط صحية',
                'name_en' => 'Sanitary Pads',
                'description' => 'فوط صحية للنساء والفتيات',
                'unit' => 'علبة',
                'category' => AidType::CATEGORY_HYGIENE,
                'icon' => 'female',
                'color' => 'pink',
            ],

            // Basic needs
            [
                'name' => 'أسطوانات غاز',
                'name_en' => 'Gas Cylinders',
                'description' => 'أسطوانات غاز للطبخ والتدفئة',
                'unit' => 'أسطوانة',
                'category' => AidType::CATEGORY_BASIC,
                'icon' => 'fire',
                'color' => 'warning',
            ],
            [
                'name' => 'مولدات كهرباء',
                'name_en' => 'Power Generators',
                'description' => 'مولدات كهربائية صغيرة للاستخدام الأسري',
                'unit' => 'مولد',
                'category' => AidType::CATEGORY_BASIC,
                'icon' => 'bolt',
                'color' => 'warning',
            ],
        ];

        foreach ($aidTypes as $aidType) {
            AidType::create($aidType);
        }

        $this->command->info('AidType seeder completed successfully!');
        $this->command->info('Created ' . count($aidTypes) . ' aid types.');
    }
}