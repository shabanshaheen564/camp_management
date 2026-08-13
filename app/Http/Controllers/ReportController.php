<?php

namespace App\Http\Controllers;

use App\Models\AidDistribution;
use App\Models\Camp;
use App\Models\FamilyMember;
use App\Models\Guardian;
use App\Support\AgeGroupQuery;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $totalCamps    = Camp::active()->count();
            $totalFamilies = Guardian::count();
            $totalMembers  = FamilyMember::count();
            $totalPersons  = $totalFamilies + $totalMembers;
            $totalAids     = AidDistribution::count();

            $camps = Camp::active()->orderBy('name')->get();

            $campsData = Camp::active()
                ->withCount('guardians')
                ->orderByDesc('guardians_count')
                ->take(8)
                ->get()
                ->map(fn ($c) => [
                    'name'  => $c->name,
                    'count' => $c->guardians_count,
                ]);

            $monthlyAids = collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                $count = AidDistribution::whereYear('distribution_date', $date->year)
                    ->whereMonth('distribution_date', $date->month)
                    ->count();

                return [
                    'month' => $date->translatedFormat('M Y'),
                    'count' => $count,
                ];
            });

            $monthlyGrowth = collect(range(5, 0))->map(function ($i) {
                $date = now()->subMonths($i);
                $count = Guardian::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                return [
                    'month' => $date->translatedFormat('M Y'),
                    'count' => $count,
                ];
            });

            $ageGroups = AgeGroupQuery::counts(FamilyMember::query());
        } else {
            $campId = $user->camp_id;

            $totalCamps    = Camp::active()->where('id', $campId)->count();
            $totalFamilies = Guardian::where('camp_id', $campId)->count();
            $totalMembers  = FamilyMember::whereHas('guardian', fn ($q) => $q->where('camp_id', $campId))->count();
            $totalPersons  = $totalFamilies + $totalMembers;
            $totalAids     = AidDistribution::where('camp_id', $campId)->count();

            $camps = Camp::active()->where('id', $campId)->orderBy('name')->get();

            $campsData = Camp::active()
                ->where('id', $campId)
                ->withCount('guardians')
                ->orderByDesc('guardians_count')
                ->take(8)
                ->get()
                ->map(fn ($c) => [
                    'name'  => $c->name,
                    'count' => $c->guardians_count,
                ]);

            $monthlyAids = collect(range(5, 0))->map(function ($i) use ($campId) {
                $date = now()->subMonths($i);
                $count = AidDistribution::where('camp_id', $campId)
                    ->whereYear('distribution_date', $date->year)
                    ->whereMonth('distribution_date', $date->month)
                    ->count();

                return [
                    'month' => $date->translatedFormat('M Y'),
                    'count' => $count,
                ];
            });

            $monthlyGrowth = collect(range(5, 0))->map(function ($i) use ($campId) {
                $date = now()->subMonths($i);
                $count = Guardian::where('camp_id', $campId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                return [
                    'month' => $date->translatedFormat('M Y'),
                    'count' => $count,
                ];
            });

            $ageGroups = AgeGroupQuery::counts(
                FamilyMember::whereHas('guardian', fn ($q) => $q->where('camp_id', $campId))
            );
        }

        return view('camp_management.reports', compact(
            'totalCamps', 'totalFamilies', 'totalPersons', 'totalAids',
            'campsData', 'monthlyAids', 'monthlyGrowth', 'ageGroups', 'camps'
        ));
    }

    public function printStatistics()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $totalCamps    = Camp::active()->count();
            $totalFamilies = Guardian::count();
            $totalMembers  = FamilyMember::count();
            $totalPersons  = $totalFamilies + $totalMembers;
            $totalAids     = AidDistribution::count();

            $camps = Camp::active()
                ->withCount('guardians')
                ->orderBy('name')
                ->get();

            $ageGroups = AgeGroupQuery::counts(FamilyMember::query());
        } else {
            $campId = $user->camp_id;
            $totalCamps    = Camp::active()->where('id', $campId)->count();
            $totalFamilies = Guardian::where('camp_id', $campId)->count();
            $totalMembers  = FamilyMember::whereHas('guardian', fn ($q) => $q->where('camp_id', $campId))->count();
            $totalPersons  = $totalFamilies + $totalMembers;
            $totalAids     = AidDistribution::where('camp_id', $campId)->count();

            $camps = Camp::active()
                ->where('id', $campId)
                ->withCount('guardians')
                ->orderBy('name')
                ->get();

            $ageGroups = AgeGroupQuery::counts(
                FamilyMember::whereHas('guardian', fn ($q) => $q->where('camp_id', $campId))
            );
        }

        $totalDisplaced = $totalFamilies + $totalMembers;

        return view('camp_management.reports_print', compact(
            'totalCamps', 'totalFamilies', 'totalMembers', 'totalPersons',
            'totalAids', 'camps', 'ageGroups', 'totalDisplaced'
        ));
    }

    public function exportCamps()
    {
        $user = auth()->user();

        $query = Camp::withCount('guardians')->orderBy('name');
        if (!$user->isAdmin()) {
            $query->where('id', $user->camp_id);
        }
        $camps = $query->get();

        $filename = 'camps_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($camps) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Name', 'Location', 'Latitude', 'Longitude', 'Manager', 'Phone', 'Capacity', 'Status', 'Active', 'Guardians Count', 'Created At']);
            foreach ($camps as $camp) {
                fputcsv($handle, [
                    $camp->id,
                    $camp->name,
                    $camp->location ?? '',
                    $camp->latitude ?? '',
                    $camp->longitude ?? '',
                    $camp->manager ?? '',
                    $camp->phone ?? '',
                    $camp->capacity ?? '',
                    $camp->status ?? '',
                    $camp->is_active ? 'Yes' : 'No',
                    $camp->guardians_count,
                    $camp->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportFamilies(Request $request)
    {
        $user = auth()->user();
        $campId = $request->query('camp_id');

        $query = Guardian::with('camp');
        if ($user->isAdmin()) {
            if ($campId) {
                $query->where('camp_id', $campId);
            }
        } else {
            $query->where('camp_id', $user->camp_id);
        }
        $families = $query->orderBy('first_name')->get();

        $effectiveCampId = $user->isAdmin() ? $campId : $user->camp_id;
        $campName = $effectiveCampId ? Camp::find($effectiveCampId)?->name : 'All';
        $filename = 'families_' . ($campName ? str_replace(' ', '_', $campName) : 'all') . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($families) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Guardian Name', 'Card ID', 'Phone', 'Gender', 'Date of Birth', 'Nationality', 'Marital Status', 'Address', 'Disabled', 'Camp', 'Family Members Count', 'Created At']);

            foreach ($families as $family) {
                fputcsv($handle, [
                    $family->id,
                    $family->full_name,
                    $family->card_id ?? '',
                    $family->phone ?? '',
                    $family->gender ?? '',
                    $family->date_of_birth?->format('Y-m-d') ?? '',
                    $family->nationality ?? '',
                    $family->marital_status ?? '',
                    $family->address ?? '',
                    $family->is_disabled ? 'Yes' : 'No',
                    $family->camp?->name ?? 'N/A',
                    $family->familyMembers()->count(),
                    $family->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportMembers(Request $request)
    {
        $user = auth()->user();
        $campId = $request->query('camp_id');

        $query = FamilyMember::whereHas('guardian', function ($q) use ($user, $campId) {
            if ($user->isAdmin()) {
                if ($campId) {
                    $q->where('camp_id', $campId);
                }
            } else {
                $q->where('camp_id', $user->camp_id);
            }
        })->with('guardian');

        $members = $query->orderBy('name')->get();

        $effectiveCampId = $user->isAdmin() ? $campId : $user->camp_id;
        $campName = $effectiveCampId ? Camp::find($effectiveCampId)?->name : 'All';
        $filename = 'members_' . ($campName ? str_replace(' ', '_', $campName) : 'all') . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Name', 'Card ID', 'Gender', 'Date of Birth', 'Nationality', 'Relationship', 'Phone', 'Disabled', 'Marital Status', 'Guardian Card ID', 'Guardian', 'Guardian Marital Status', 'Camp', 'Created At']);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->id,
                    $member->name,
                    $member->card_id ?? '',
                    $member->gender ?? '',
                    $member->date_of_birth?->format('Y-m-d') ?? '',
                    $member->nationality ?? '',
                    $member->relationship ?? '',
                    $member->phone_number ?? '',
                    $member->is_disabled ? 'Yes' : 'No',
                    $member->marital_status ?? '',
                    $member->guardian?->card_id ?? '',
                    $member->guardian?->full_name ?? 'N/A',
                    $member->guardian?->marital_status ?? '',
                    $member->guardian?->camp?->name ?? 'N/A',
                    $member->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
