<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleXLSX;
use Illuminate\Support\Facades\Storage;

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

        $rows = [];
        $headers = [];

        if ($file->getClientOriginalExtension() === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $headers = fgetcsv($handle, 0, ',');
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

$storedPath = $file->store('imports');
session(['import_file' => $storedPath, 'import_extension' => $file->getClientOriginalExtension()]);
        return view('camp_management.camps_import_map', compact('headers', 'rows', 'dbFields'));
    }

    /**
     * Execute import with column mapping.
     */
    public function importExecute(Request $request)
    {
        $request->validate([
            'mapping' => 'required|array',
        ]);
$path = $request->input('import_file');
$extension = $request->input('import_extension', 'xlsx');

$fullPath = $path ? storage_path('app/' . $path) : null;

if (!$fullPath || !file_exists($fullPath)) {
    return redirect()->route('camps.import.form')->with('error', 'انتهت صلاحية الملف. يرجى رفعه مرة أخرى.');
}

      

        $mapping = $request->input('mapping', []);
        $nameColumn = $mapping['name'] ?? null;

        if (!$nameColumn) {
            return redirect()->route('camps.import.form')->with('error', 'يرجى تحديد عمود اسم المخيم.');
        }

        $rows = [];
        if ($extension === 'csv') {
            $handle = fopen($fullPath, 'r');
            if ($handle !== false) {
                $headers = fgetcsv($handle, 0, ',');
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rows[] = array_combine($headers, $row);
                }
                fclose($handle);
            }
        } else {
            if ($xlsx = SimpleXLSX::parse($fullPath)) {
                $headers = $xlsx->headers()[0];
                foreach ($xlsx->rows() as $row) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $name = trim($row[$nameColumn] ?? '');
            if (!$name) {
                $results['errors'][] = "السطر " . ($index + 2) . ": اسم المخيم مفقود";
                continue;
            }

            $data = ['name' => $name];

            foreach ($mapping as $dbField => $excelColumn) {
                if ($dbField === 'name' || !$excelColumn) continue;
                $value = trim($row[$excelColumn] ?? '');
                if ($value === '') continue;

                switch ($dbField) {
                    case 'capacity':
                    case 'current_occupancy':
                        $data[$dbField] = (int) $value;
                        break;
                    case 'latitude':
                    case 'longitude':
                        $data[$dbField] = (float) $value;
                        break;
                    case 'is_active':
                        $data[$dbField] = in_array(strtolower($value), ['1', 'نعم', 'yes', 'true', 'active']);
                        break;
                    case 'status':
                        $data[$dbField] = in_array(strtolower($value), ['inactive', 'full']) ? strtolower($value) : 'active';
                        break;
                    default:
                        $data[$dbField] = $value;
                }
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
        if ($path) {
    Storage::delete($path);
}

        

        return redirect()->route('camps.index')->with('success', 
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }
}
