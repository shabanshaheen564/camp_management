<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AidDistribution;
use App\Models\AidType;
use App\Models\Camp;
use App\Models\User;

class AidDistributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $camps = Camp::all();
        $aidTypes = AidType::all();
        $admin = User::where('email', 'admin@camp.org')->first();

        if ($camps->isEmpty() || $aidTypes->isEmpty() || !$admin) {
            $this->command->warn('Missing required data: camps, aid types, or admin user. Please seed them first.');
            return;
        }

        // نجيب كل مخيم بالاسم صراحةً بدل الاعتماد على ترتيب/فهرس، مع سقوط
        // آمن (fallback) على أول مخيم موجود لو الاسم تغيّر لأي سبب.
        $campByName = fn (string $name) => $camps->firstWhere('name', $name) ?? $camps->first();
        $aidTypeByCategory = fn (string $category) => $aidTypes->where('category', $category)->first() ?? $aidTypes->first();

        $distributions = [
            [
                'camp_id' => $campByName('مخيم الأمل - الرمال الغربي')->id,
                'aid_type_id' => $aidTypeByCategory('food')->id,
                'available_quantity' => 1000.00,
                'distributed_quantity' => 450.00,
                'target_beneficiaries' => 200,
                'distribution_basis' => 'individual',
                'time_period' => 'weekly',
                'distribution_date' => now()->subDays(7),
                'expiry_date' => now()->addDays(14),
                'status' => 'active',
                'priority' => 'high',
                'special_notes' => 'توزيع أسبوعي للمواد الغذائية الأساسية',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
            [
                'camp_id' => $campByName('مخيم الكرامة - الشيخ عجلين')->id,
                'aid_type_id' => $aidTypeByCategory('water')->id,
                'available_quantity' => 500.00,
                'distributed_quantity' => 200.00,
                'target_beneficiaries' => 150,
                'distribution_basis' => 'family',
                'time_period' => 'daily',
                'distribution_date' => now()->subDays(3),
                'expiry_date' => null,
                'status' => 'active',
                'priority' => 'urgent',
                'special_notes' => 'توزيع المياه النظيفة يومياً',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
            [
                'camp_id' => $campByName('مخيم الصمود - تل الهوا')->id,
                'aid_type_id' => $aidTypeByCategory('medical')->id,
                'available_quantity' => 200.00,
                'distributed_quantity' => 200.00,
                'target_beneficiaries' => 100,
                'distribution_basis' => 'individual',
                'time_period' => 'monthly',
                'distribution_date' => now()->subDays(30),
                'expiry_date' => now()->addDays(30),
                'status' => 'completed',
                'priority' => 'medium',
                'special_notes' => 'توزيع الأدوية والمستلزمات الطبية',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
            [
                'camp_id' => $campByName('مخيم النور - النصر')->id,
                'aid_type_id' => $aidTypeByCategory('clothing')->id,
                'available_quantity' => 300.00,
                'distributed_quantity' => 0.00,
                'target_beneficiaries' => 80,
                'distribution_basis' => 'family',
                'time_period' => 'monthly',
                'distribution_date' => now()->addDays(5),
                'expiry_date' => null,
                'status' => 'pending',
                'priority' => 'low',
                'special_notes' => 'توزيع الملابس الشتوية للعائلات',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
            [
                'camp_id' => $campByName('مخيم الرجاء - الشاطئ')->id,
                'aid_type_id' => $aidTypeByCategory('hygiene')->id,
                'available_quantity' => 150.00,
                'distributed_quantity' => 75.00,
                'target_beneficiaries' => 60,
                'distribution_basis' => 'household',
                'time_period' => 'weekly',
                'distribution_date' => now()->subDays(2),
                'expiry_date' => now()->addDays(21),
                'status' => 'active',
                'priority' => 'medium',
                'special_notes' => 'مواد النظافة الشخصية والمنزلية',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
            [
                'camp_id' => $campByName('مخيم الصمود - تل الهوا')->id,
                'aid_type_id' => $aidTypeByCategory('shelter')->id,
                'available_quantity' => 50.00,
                'distributed_quantity' => 0.00,
                'target_beneficiaries' => 25,
                'distribution_basis' => 'family',
                'time_period' => 'monthly',
                'distribution_date' => now()->addDays(10),
                'expiry_date' => null,
                'status' => 'pending',
                'priority' => 'urgent',
                'special_notes' => 'مواد البناء وإصلاح المأوى',
                'created_by' => $admin->id,
                'managed_by' => $admin->id,
            ],
        ];

        foreach ($distributions as $distribution) {
            AidDistribution::updateOrCreate(
                [
                    'camp_id' => $distribution['camp_id'],
                    'aid_type_id' => $distribution['aid_type_id'],
                    'distribution_date' => $distribution['distribution_date'],
                ],
                $distribution
            );
        }

        $this->command->info('Created ' . count($distributions) . ' aid distributions.');

        // توليد تخصيصات تجريبية للعائلات ضمن أول توزيع فقط، مع حماية كاملة
        // من أي مخيم غير مرتبط (camp أو camp_id غير صالح).
        $firstDistribution = AidDistribution::first();
        if ($firstDistribution && $firstDistribution->camp) {
            try {
                $firstDistribution->generateAllocations();
                $this->command->info('Generated family allocations for the first distribution.');
            } catch (\Exception $e) {
                $this->command->warn('Could not generate family allocations: ' . $e->getMessage());
            }
        } else {
            $this->command->warn('Skipped generating allocations: first distribution has no linked camp.');
        }
    }
}
