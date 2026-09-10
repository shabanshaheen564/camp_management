<?php

namespace Database\Seeders;

use App\Models\AidDistribution;
use App\Models\AidType;
use App\Models\Camp;
use App\Models\FamilyMember;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WestGazaDemoSeeder extends Seeder
{
    /**
     * Synthetic demo data for the West Gaza study area.
     *
     * The records are intentionally fictional and are generated with a fixed
     * random seed so repeated db:seed runs remain deterministic and idempotent.
     */
    public function run(): void
    {
        mt_srand(20260910);

        $admin = User::where('email', 'admin@camp.org')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $aidTypes = AidType::all();

        if (!$admin || !$supervisorRole || $aidTypes->isEmpty()) {
            $this->command->warn('WestGazaDemoSeeder skipped: admin, supervisor role, or aid types are missing.');
            return;
        }

        $campNames = [
            'مخيم الوفاء - غرب غزة 01',
            'مخيم الأمان - غرب غزة 02',
            'مخيم الكرامة - غرب غزة 03',
            'مخيم الأمل - غرب غزة 04',
            'مخيم الصمود - غرب غزة 05',
            'مخيم النور - غرب غزة 06',
            'مخيم الرحمة - غرب غزة 07',
            'مخيم الإخاء - غرب غزة 08',
            'مخيم السلام - غرب غزة 09',
            'مخيم العودة - غرب غزة 10',
        ];

        $zones = [
            'الرمال الغربي',
            'تل الهوا',
            'الشيخ عجلين',
            'النصر',
            'الزيتون الغربي',
            'منطقة الشاطئ',
        ];

        $firstNames = [
            'محمد', 'أحمد', 'محمود', 'يوسف', 'عمر', 'خالد', 'عبدالله', 'إبراهيم',
            'ياسين', 'سليم', 'سامر', 'باسل', 'رامي', 'فادي', 'طارق', 'زياد',
            'نور', 'سارة', 'ريم', 'آية', 'دعاء', 'سلمى', 'هبة', 'رنا', 'ليان',
            'جنى', 'ملك', 'مريم', 'هدى', 'أمل', 'سناء', 'لينا', 'عبير',
        ];

        $familyNames = [
            'الحلبي', 'البرغوثي', 'شحادة', 'مقداد', 'أبو علي', 'الديك', 'ريان',
            'صيام', 'قديح', 'العطار', 'أبو زايدة', 'الحداد', 'النخالة', 'أبو معيلق',
            'السقا', 'بركة', 'الهور', 'الأسطل', 'شبير', 'اللوح', 'النجار', 'مطر',
            'أبو دقة', 'الترك', 'شقير', 'حمدان', 'ياسين', 'عودة', 'سليمان', 'عوض',
        ];

        $maritalStatuses = ['married', 'married', 'married', 'widowed', 'divorced', 'single'];
        $aidCategories = ['food', 'water', 'medical', 'clothing', 'shelter', 'hygiene', 'basic'];

        $createdCamps = 0;
        $createdGuardians = 0;
        $createdMembers = 0;
        $createdUsers = 0;
        $createdDistributions = 0;

        foreach ($campNames as $campIndex => $campName) {
            $campNumber = $campIndex + 1;

            // Randomized point constrained to the approximate West Gaza study area.
            $latitude = round(mt_rand(31485000, 31535000) / 1000000, 6);
            $longitude = round(mt_rand(34425000, 34465000) / 1000000, 6);
            $capacity = mt_rand(280, 650);

            $camp = Camp::updateOrCreate(
                ['name' => $campName],
                [
                    'location' => $zones[mt_rand(0, count($zones) - 1)] . '، غرب مدينة غزة',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'capacity' => $capacity,
                    'current_occupancy' => 0,
                    'manager' => 'إدارة مخيم ' . $campNumber,
                    'phone' => '+970599' . str_pad((string) (700000 + $campNumber), 6, '0', STR_PAD_LEFT),
                    'description' => 'بيانات تجريبية عشوائية لمنطقة الدراسة غرب مدينة غزة',
                    'status' => 'active',
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]
            );
            $createdCamps++;

            $email = sprintf('westgaza.rep%02d@camp.org', $campNumber);
            $representative = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'مندوب مخيم غرب غزة ' . str_pad((string) $campNumber, 2, '0', STR_PAD_LEFT),
                    'password' => Hash::make('westgaza123'),
                    'role_id' => $supervisorRole->id,
                    'camp_id' => $camp->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $createdUsers++;

            $familyCount = mt_rand(10, 16);

            for ($familyIndex = 1; $familyIndex <= $familyCount; $familyIndex++) {
                $first = $firstNames[mt_rand(0, count($firstNames) - 1)];
                $second = $firstNames[mt_rand(0, count($firstNames) - 1)];
                $third = $firstNames[mt_rand(0, count($firstNames) - 1)];
                $family = $familyNames[mt_rand(0, count($familyNames) - 1)];
                $gender = mt_rand(0, 1) ? 'male' : 'female';
                $maritalStatus = $maritalStatuses[mt_rand(0, count($maritalStatuses) - 1)];
                $birthYear = mt_rand(1960, 1998);
                $cardId = sprintf('WG%02d%03d', $campNumber, $familyIndex);

                Guardian::updateOrCreate(
                    ['card_id' => $cardId],
                    [
                        'camp_id' => $camp->id,
                        'first_name' => $first,
                        'second_name' => $second,
                        'third_name' => $third,
                        'family_name' => $family,
                        'date_of_birth' => sprintf('%04d-%02d-%02d', $birthYear, mt_rand(1, 12), mt_rand(1, 28)),
                        'gender' => $gender,
                        'card_id' => $cardId,
                        'marital_status' => $maritalStatus,
                        'nationality' => 'فلسطيني',
                        'family_member_number' => 0,
                        'is_disabled' => mt_rand(1, 10) === 1,
                    ]
                );
                $createdGuardians++;

                $guardian = Guardian::where('card_id', $cardId)->firstOrFail();

                if ($maritalStatus === 'single') {
                    $memberCount = mt_rand(0, 1);
                } elseif ($maritalStatus === 'widowed' || $maritalStatus === 'divorced') {
                    $memberCount = mt_rand(1, 4);
                } else {
                    $memberCount = mt_rand(2, 5);
                }

                for ($memberIndex = 1; $memberIndex <= $memberCount; $memberIndex++) {
                    $memberFirst = $firstNames[mt_rand(0, count($firstNames) - 1)];
                    $memberCardId = sprintf('WG%02d%03d%02d', $campNumber, $familyIndex, $memberIndex);
                    $age = mt_rand(1, 45);
                    $birthDate = now()->subYears($age)->subDays(mt_rand(0, 364))->format('Y-m-d');
                    $memberGender = mt_rand(0, 1) ? 'male' : 'female';

                    FamilyMember::updateOrCreate(
                        ['card_id' => $memberCardId],
                        [
                            'guardian_id' => $guardian->id,
                            'name' => $memberFirst . ' ' . $second . ' ' . $family,
                            'relationship' => $age < 18 ? 'child' : 'relative',
                            'gender' => $memberGender,
                            'card_id' => $memberCardId,
                            'date_of_birth' => $birthDate,
                            'nationality' => 'فلسطيني',
                            'phone_number' => $age >= 18 ? '+970599' . str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT) : null,
                            'is_disabled' => mt_rand(1, 12) === 1,
                            'marital_status' => $age < 18 ? 'single' : ($age > 20 && mt_rand(1, 4) === 1 ? 'married' : 'single'),
                        ]
                    );
                    $createdMembers++;
                }

                $guardian->updateFamilyMemberCount();
            }

            $camp->updateOccupancy();

            // Two aid distributions per synthetic camp using different aid categories.
            foreach ([0, 1] as $distributionIndex) {
                $category = $aidCategories[($campIndex + $distributionIndex) % count($aidCategories)];
                $aidType = $aidTypes->where('category', $category)->first() ?? $aidTypes->first();
                $available = mt_rand(100, 1000);
                $distributed = mt_rand(0, $available);
                $daysAgo = 2 + ($campIndex * 2) + $distributionIndex;
                $basis = ['individual', 'family', 'household'][mt_rand(0, 2)];
                $status = $distributed >= $available ? 'completed' : (mt_rand(0, 1) ? 'active' : 'pending');
                $distributionDate = now()->subDays($daysAgo)->startOfDay();

                $distribution = AidDistribution::updateOrCreate(
                    [
                        'camp_id' => $camp->id,
                        'aid_type_id' => $aidType->id,
                        'distribution_date' => $distributionDate,
                    ],
                    [
                        'camp_id' => $camp->id,
                        'aid_type_id' => $aidType->id,
                        'available_quantity' => $available,
                        'distributed_quantity' => $distributed,
                        'time_period' => ['daily', 'weekly', 'monthly'][mt_rand(0, 2)],
                        'target_beneficiaries' => mt_rand(20, max(20, $familyCount * 5)),
                        'distribution_basis' => $basis,
                        'distribution_date' => $distributionDate,
                        'expiry_date' => now()->addDays(mt_rand(7, 45))->startOfDay(),
                        'status' => $status,
                        'priority' => ['low', 'medium', 'high', 'urgent'][mt_rand(0, 3)],
                        'special_notes' => 'بيانات مساعدة تجريبية عشوائية - منطقة الدراسة غرب غزة',
                        'created_by' => $admin->id,
                        'managed_by' => $representative->id,
                    ]
                );

                try {
                    $distribution->generateAllocations();
                } catch (\Throwable $e) {
                    $this->command->warn('Skipped allocations for distribution #' . $distribution->id . ': ' . $e->getMessage());
                }

                $createdDistributions++;
            }
        }

        $this->command->info('West Gaza demo data seeded successfully.');
        $this->command->info("Camps: {$createdCamps} | Representatives: {$createdUsers} | Families: {$createdGuardians} | Members: {$createdMembers} | Aid distributions: {$createdDistributions}");
        $this->command->info('Representative login password: westgaza123');
    }
}
