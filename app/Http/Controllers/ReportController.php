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
            'campsData', 'monthlyAids', 'monthlyGrowth', 'ageGroups'
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
            fputcsv($handle, ['ID', 'Name', 'Location', 'Active', 'Guardians Count', 'Created At']);

            foreach ($camps as $camp) {
                fputcsv($handle, [
                    $camp->id,
                    $camp->name,
                    $camp->location ?? '',
                    $camp->is_active ? 'Yes' : 'No',
                    $camp->guardians_count,
                    $camp->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
