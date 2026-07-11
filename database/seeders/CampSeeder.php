<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@camp.org')->first();

        $camps = [
            [
                'name' => 'مخيم الأمل',
                'location' => 'غزة',
                'latitude' => 31.5017,
                'longitude' => 34.4668,
                'capacity' => 1000,
                'current_occupancy' => 0,
                'manager' => 'أحمد محمد',
                'phone' => '+970123456789',
                'description' => 'مخيم للنازحين يوفر الخدمات الأساسية والرعاية الصحية',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم النور',
                'location' => 'رفح',
                'latitude' => 31.2887,
                'longitude' => 34.2372,
                'capacity' => 800,
                'current_occupancy' => 0,
                'manager' => 'فاطمة أحمد',
                'phone' => '+970987654321',
                'description' => 'مخيم مؤقت للعائلات النازحة مع خدمات التعليم والصحة',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'مخيم السلام',
                'location' => 'خان يونس',
                'latitude' => 31.3417,
                'longitude' => 34.3083,
                'capacity' => 1200,
                'current_occupancy' => 0,
                'manager' => 'محمد حسن',
                'phone' => '+970555123456',
                'description' => 'مخيم كبير يستوعب عدد كبير من العائلات مع مرافق متكاملة',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ]
        ];

        foreach ($camps as $campData) {
            Camp::updateOrCreate(
                ['name' => $campData['name']],
                $campData
            );
        }

        // Assign camps to supervisors
        $this->assignCampsToSupervisors();
    }

    private function assignCampsToSupervisors()
    {
        $camp1 = Camp::where('name', 'مخيم الأمل')->first();
        $camp2 = Camp::where('name', 'مخيم النور')->first();

        $supervisor1 = User::where('email', 'ahmed.supervisor@camp.org')->first();
        $supervisor2 = User::where('email', 'fatema.supervisor@camp.org')->first();

        if ($camp1 && $supervisor1) {
            $supervisor1->update(['camp_id' => $camp1->id]);
        }

        if ($camp2 && $supervisor2) {
            $supervisor2->update(['camp_id' => $camp2->id]);
        }
    }
}