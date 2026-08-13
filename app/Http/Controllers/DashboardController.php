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
        $user = auth()->user();

        if ($user->isAdmin()) {
            $totalCamps     = Camp::count();
            $activeCamps    = Camp::active()->count();
            $totalFamilies  = Guardian::count();
            $totalMembers   = FamilyMember::whereHas('guardian')->count();
            $totalDisplaced = $totalFamilies + $totalMembers;
            $totalCapacity  = Camp::sum('capacity');
            $totalAid       = AidDistribution::count();
            $recent_camps   = Camp::latest()->take(5)->get();
        } else {
            $campId = $user->camp_id;
            $totalCamps     = Camp::where('id', $campId)->count();
            $activeCamps    = Camp::active()->where('id', $campId)->count();
            $totalFamilies  = Guardian::where('camp_id', $campId)->count();
            $totalMembers   = FamilyMember::whereHas('guardian', fn($q) => $q->where('camp_id', $campId))->count();
            $totalDisplaced = $totalFamilies + $totalMembers;
            $totalCapacity  = Camp::where('id', $campId)->sum('capacity');
            $totalAid       = AidDistribution::where('camp_id', $campId)->count();
            $recent_camps   = Camp::where('id', $campId)->latest()->take(5)->get();
        }

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

        $notifications = $user->notifications()->latest()->take(3)->get();
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        return view('camp_management.dashboard', compact('stats', 'recent_camps', 'notifications', 'unreadNotificationsCount'));
    }
}