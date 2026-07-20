<?php

namespace App\Http\Controllers;

use App\Models\AidDistribution;
use App\Models\Camp;
use App\Models\FamilyMember;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        // إحصاءات عامة
        $totalCamps    = Camp::where('is_active', true)->count();
        $totalFamilies = Guardian::count();
        $totalMembers  = FamilyMember::count();
        $totalPersons  = $totalFamilies + $totalMembers;
        $totalAids     = AidDistribution::count();

        // قائمة المخيمات للتصفية
        $camps = Camp::where('is_active', true)->orderBy('name')->get();

        // توزيع النازحين على المخيمات (مخطط دائري)
        $campsData = Camp::where('is_active', true)
            ->withCount('guardians')
            ->orderByDesc('guardians_count')
            ->take(8)
            ->get()
            ->map(fn($c) => [
                'name'  => $c->name,
                'count' => $c->guardians_count,
            ]);

        // المساعدات الموزعة شهرياً آخر 6 أشهر (مخطط أعمدة)
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

        // نمو الأعداد شهرياً آخر 6 أشهر (مخطط خطي)
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

        // توزيع أعمار الأفراد
       $ageGroups = [
    'أقل من 18' => FamilyMember::whereNotNull('date_of_birth')
        ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) < 18")->count(),
    '18 - 35' => FamilyMember::whereNotNull('date_of_birth')
        ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 18 AND 35")->count(),
    '36 - 60' => FamilyMember::whereNotNull('date_of_birth')
        ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 36 AND 60")->count(),
    'أكبر من 60' => FamilyMember::whereNotNull('date_of_birth')
        ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) > 60")->count(),
];

        return view('camp_management.reports', compact(
            'totalCamps', 'totalFamilies', 'totalPersons', 'totalAids',
            'campsData', 'monthlyAids', 'monthlyGrowth', 'ageGroups', 'camps'
        ));
    }

    /**
     * Show printable statistics report.
     */
    public function printStatistics()
    {
        $totalCamps    = Camp::where('is_active', true)->count();
        $totalFamilies = Guardian::count();
        $totalMembers  = FamilyMember::count();
        $totalPersons  = $totalFamilies + $totalMembers;
        $totalAids     = AidDistribution::count();

        $camps = Camp::where('is_active', true)
            ->withCount('guardians')
            ->orderBy('name')
            ->get();

        $ageGroups = [
            'أقل من 18' => FamilyMember::whereNotNull('date_of_birth')
                ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) < 18")->count(),
            '18 - 35' => FamilyMember::whereNotNull('date_of_birth')
                ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 18 AND 35")->count(),
            '36 - 60' => FamilyMember::whereNotNull('date_of_birth')
                ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 36 AND 60")->count(),
            'أكبر من 60' => FamilyMember::whereNotNull('date_of_birth')
                ->whereRaw("EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) > 60")->count(),
        ];

        $totalDisplaced = $totalFamilies + $totalMembers;

        return view('camp_management.reports_print', compact(
            'totalCamps', 'totalFamilies', 'totalMembers', 'totalPersons',
            'totalAids', 'camps', 'ageGroups', 'totalDisplaced'
        ));
    }

    /**
     * Export camps list as CSV for opening in Excel.
     */
    public function exportCamps()
    {
        $camps = Camp::withCount('guardians')->orderBy('name')->get();

        $filename = 'camps_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($camps) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            // Header row
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

    /**
     * Export families for a specific camp as CSV.
     */
    public function exportFamilies(Request $request)
    {
        $campId = $request->query('camp_id');
        
        $query = Guardian::with('camp');
        if ($campId) {
            $query->where('camp_id', $campId);
        }
        $families = $query->orderBy('first_name')->get();

        $campName = $campId ? Camp::find($campId)?->name : 'All';
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

    /**
     * Export family members for a specific camp as CSV.
     */
    public function exportMembers(Request $request)
    {
        $campId = $request->query('camp_id');

        $query = FamilyMember::whereHas('guardian', function ($q) use ($campId) {
            if ($campId) {
                $q->where('camp_id', $campId);
            }
        })->with('guardian');
        
        $members = $query->orderBy('name')->get();

        $campName = $campId ? Camp::find($campId)?->name : 'All';
        $filename = 'members_' . ($campName ? str_replace(' ', '_', $campName) : 'all') . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Name', 'Card ID', 'Gender', 'Date of Birth', 'Nationality', 'Relationship', 'Phone', 'Disabled', 'Guardian Card ID', 'Guardian', 'Camp', 'Created At']);

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
                    $member->guardian?->card_id ?? '',
                    $member->guardian?->full_name ?? 'N/A',
                    $member->guardian?->camp?->name ?? 'N/A',
                    $member->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
