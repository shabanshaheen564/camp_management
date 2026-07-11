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
        // Get existing camps, aid types, and users
        $camps = Camp::all();
        $aidTypes = AidType::all();
        $users = User::all();

        if ($camps->isEmpty() || $aidTypes->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Missing required data: camps, aid types, or users. Please seed them first.');
            return;
        }

        // Create sample aid distributions
        $distributions = [
            [
                'camp_id' => $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'food')->first()->id ?? $aidTypes->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ],
            [
                'camp_id' => $camps->count() > 1 ? $camps->skip(1)->first()->id : $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'water')->first()->id ?? $aidTypes->skip(1)->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ],
            [
                'camp_id' => $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'medical')->first()->id ?? $aidTypes->skip(2)->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ],
            [
                'camp_id' => $camps->count() > 2 ? $camps->skip(2)->first()->id : $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'clothing')->first()->id ?? $aidTypes->skip(3)->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ],
            [
                'camp_id' => $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'hygiene')->first()->id ?? $aidTypes->skip(4)->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ],
            [
                'camp_id' => $camps->count() > 1 ? $camps->skip(1)->first()->id : $camps->first()->id,
                'aid_type_id' => $aidTypes->where('category', 'shelter')->first()->id ?? $aidTypes->skip(5)->first()->id,
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
                'created_by' => $users->first()->id,
                'managed_by' => $users->first()->id,
            ]
        ];

        foreach ($distributions as $distribution) {
            AidDistribution::create($distribution);
        }

        $this->command->info('Created ' . count($distributions) . ' aid distributions.');
        
        // Generate some family allocations for the first distribution
        $firstDistribution = AidDistribution::first();
        if ($firstDistribution) {
            try {
                $firstDistribution->generateAllocations();
                $this->command->info('Generated family allocations for the first distribution.');
            } catch (\Exception $e) {
                $this->command->warn('Could not generate family allocations: ' . $e->getMessage());
            }
        }
    }
}