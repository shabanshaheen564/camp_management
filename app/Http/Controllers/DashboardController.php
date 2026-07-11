<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Guardian;
use App\Models\FamilyMember;
use App\Models\AidDistribution;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCamps     = Camp::count();
        $activeCamps    = Camp::where('is_active', true)->count();
        $totalFamilies  = Guardian::count();
        $totalMembers   = FamilyMember::count();
        $totalDisplaced = $totalFamilies + $totalMembers;
        $totalCapacity  = Camp::sum('capacity');
        $totalAid       = AidDistribution::count();

        $stats = [
            'total_camps'    => $totalCamps,
            'active_camps'   => $activeCamps,
            'total_families' => $totalFamilies,
            'total_displaced'=> $totalDisplaced,
            'total_aid'      => $totalAid,
            'occupancy_rate' => $totalCapacity > 0
                ? round(($totalDisplaced / $totalCapacity) * 100)
                : 0,
            'avg_family_size'=> $totalFamilies > 0
                ? round($totalDisplaced / $totalFamilies, 1)
                : 0,
        ];

        $recent_camps = Camp::latest()->take(5)->get();

        $alerts = [];

        $nearFull = Camp::where('is_active', true)->get()->filter(function ($camp) {
            $occupied = $camp->guardians()->count()
                + $camp->guardians()->withCount('familyMembers')->get()->sum('family_members_count');
            return $camp->capacity > 0 && $occupied >= ($camp->capacity * 0.9);
        });

        foreach ($nearFull as $camp) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'exclamation-triangle',
                'message' => "مخيم {$camp->name} قارب على الامتلاء",
                'time'    => 'نسبة الإشغال تجاوزت 90%',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type'    => 'info',
                'icon'    => 'check-circle',
                'message' => 'جميع المخيمات تعمل بشكل طبيعي',
                'time'    => 'لا توجد تنبيهات حالية',
            ];
        }

        return view('camp_management.dashboard', compact('stats', 'recent_camps', 'alerts'));
    }
}