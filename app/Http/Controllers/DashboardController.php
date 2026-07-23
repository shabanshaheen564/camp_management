<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Guardian;
use App\Models\FamilyMember;
use App\Models\AidDistribution;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCamps     = Camp::count();
        $activeCamps    = Camp::where('is_active', true)->count();
        $totalFamilies  = Guardian::count();
        $totalMembers   = FamilyMember::whereHas('guardian')->count();
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

        $notifications = Auth::user()->notifications()->latest()->take(3)->get();
        $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();

        return view('camp_management.dashboard', compact('stats', 'recent_camps', 'notifications', 'unreadNotificationsCount'));
    }
}