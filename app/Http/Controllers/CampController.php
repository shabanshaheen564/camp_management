<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleXLSX;
use Illuminate\Support\Facades\Storage;
use App\Notifications\CampCreatedNotification;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $query = Camp::withCount('guardians');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $camps        = $query->latest()->paginate(10);
        $totalCamps   = Camp::count();
        $activeCamps  = Camp::where('is_active', true)->count();
        $totalCapacity = Camp::sum('capacity');

        return view('camp_management.camps', compact(
            'camps', 'totalCamps', 'activeCamps', 'totalCapacity'
        ));
    }

  public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'manager'  => 'required|string|max:255',
        'phone'    => 'required|string|max:20',
        'capacity' => 'required|integer|min:1',
        'status'   => 'required|in:active,inactive,full',
        'latitude' => 'nullable|numeric',
        'longitude'=> 'nullable|numeric',
        'is_active'=> 'required|boolean',
    ]);

    Camp::create([
        'name'        => $request->name,
        'location'    => $request->location,
        'manager'     => $request->manager,
        'phone'       => $request->phone,
        'capacity'    => $request->capacity,
        'status'      => $request->status,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        'is_active'   => $request->is_active,
        'description' => $request->description,
        'created_by'  => auth()->id(),
    ]);

    $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
    foreach ($admins as $admin) {
        $admin->notify(new CampCreatedNotification($request->name, $request->location));
    }

    return redirect()->route('camps.index')->with('success', 'تمت إضافة المخيم بنجاح');
}

public function update(Request $request, Camp $camp)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'manager'  => 'required|string|max:255',
        'phone'    => 'required|string|max:20',
        'capacity' => 'required|integer|min:1',
        'status'   => 'required|in:active,inactive,full',
        'latitude' => 'nullable|numeric',
        'longitude'=> 'nullable|numeric',
        'is_active'=> 'required|boolean',
    ]);

    $camp->update([
        'name'        => $request->name,
        'location'    => $request->location,
        'manager'     => $request->manager,
        'phone'       => $request->phone,
        'capacity'    => $request->capacity,
        'status'      => $request->status,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        'is_active'   => $request->is_active,
        'description' => $request->description,
    ]);

    return redirect()->route('camps.index')->with('success', 'تم تحديث المخيم بنجاح');
}

    public function destroy(Camp $camp)
    {
        $camp->delete();
        return back()->with('success', 'تم حذف المخيم بنجاح.');
    }

    public function toggleStatus(Camp $camp)
    {
        $camp->update(['is_active' => !$camp->is_active]);
        $status = $camp->is_active ? 'تم تفعيل المخيم' : 'تم تعليق المخيم';
        return back()->with('success', $status);
    }
   public function show(Camp $camp)
    {
        // تأكد أن اليوزر يملك هذا المخيم فقط
        if (auth()->user()->camp_id !== $camp->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
 
        return response()->json($camp);
    }
 
    /**
     * إحصائيات المخيم
     * GET /api/camps/{id}/statistics
     */
    public function statistics(Camp $camp)
    {
        if (auth()->user()->camp_id !== $camp->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
  
        $totalFamilies    = $camp->guardians()->count();
        $totalIndividuals = $camp->guardians()->sum('family_member_number');
  
        return response()->json([
            'total_families'    => $totalFamilies,
            'total_individuals' => $totalIndividuals,
            'camp'              => $camp,
        ]);
    }

    /**
     * Show import form.
     */
    public function showImportForm()
    {
        return view('camp_management.camps_import');
    }

    /**
     * Preview Excel file and show column mapping.
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', function ($attribute, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
                    $fail('يجب أن يكون الملف من نوع: xlsx, xls, csv');
                }
            }],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $headers = [];
        $rows = [];

        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $headers = fgetcsv($handle, 0, ',');
                $headerMap = array_flip($headers);
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rows[] = array_combine($headers, $row);
                }
                fclose($handle);
            }
        } else {
            if ($xlsx = SimpleXLSX::parse($path)) {
                $headers = $xlsx->headers()[0];
                foreach ($xlsx->rows() as $row) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        $dbFields = [
            'name' => 'اسم المخيم',
            'location' => 'الموقع',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
            'capacity' => 'الطاقة الاستيعابية',
            'current_occupancy' => 'الإشغال الحالي',
            'manager' => 'مدير المخيم',
            'phone' => 'الهاتف',
            'description' => 'الوصف',
            'status' => 'الحالة',
            'is_active' => 'نشط',
        ];

        return view('camp_management.camps_import_map', compact('headers', 'rows', 'dbFields'));
    }

    /**
     * Execute import with column mapping.
     */
    public function importExecute(Request $request)
    {
        $request->validate([
            'mapping' => 'required|array',
            'import_rows' => 'required|string',
            'import_headers' => 'required|string',
        ]);

        $rows = json_decode(base64_decode($request->input('import_rows', '')), true) ?: [];
        $headers = json_decode(base64_decode($request->input('import_headers', '')), true) ?: [];
        $mapping = $request->input('mapping', []);
        $nameColumn = $mapping['name'] ?? null;

        if (!$nameColumn) {
            return redirect()->route('camps.import.form')->with('error', 'يرجى تحديد عمود اسم المخيم.');
        }

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            try {
                $this->processCampRow($row, $mapping, $nameColumn, $results);
            } catch (\Throwable $e) {
                $results['errors'][] = "السطر " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        if ($path) {
            Storage::delete($path);
        }

        return redirect()->route('camps.index')->with('success',
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }

    protected function processCampRow(array $row, array $mapping, ?string $nameColumn, array &$results): void
    {
        $name = trim((string) ($row[$nameColumn] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('اسم المخيم مفقود');
        }

        $data = ['name' => $name];

        foreach ($mapping as $dbField => $excelColumn) {
            if ($dbField === 'name' || !$excelColumn) continue;
            $rawValue = $row[$excelColumn] ?? '';
            $value = $this->normalizeExcelValue($rawValue, $dbField);
            if ($value === null || $value === '') continue;

            $data[$dbField] = $value;
        }

        $admin = User::where('email', 'admin@camp.org')->first();
        $data['created_by'] = $admin?->id;

        $camp = Camp::where('name', $name)->first();
        if ($camp) {
            $camp->update($data);
            $results['updated']++;
        } else {
            Camp::create($data);
            $results['created']++;
        }
    }

    protected function normalizeExcelValue(mixed $value, string $dbField): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value)) {
            $value = rtrim(rtrim(number_format($value, 0, '', ''), '0'), '.');
            if ($value === '') {
                $value = '0';
            }
        } elseif (is_int($value)) {
            $value = (string) $value;
        } elseif (is_string($value)) {
            $value = trim($value);
        }

        return match ($dbField) {
            'capacity', 'current_occupancy' => (int) $value,
            'latitude', 'longitude' => (float) $value,
            'is_active' => in_array(mb_strtolower($value, 'UTF-8'), ['1', 'نعم', 'yes', 'true', 'active']),
            'status' => in_array(mb_strtolower($value, 'UTF-8'), ['inactive', 'full']) ? mb_strtolower($value, 'UTF-8') : 'active',
            default => (string) $value,
        };
    }
}
