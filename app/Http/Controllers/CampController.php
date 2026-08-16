<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\User;
use App\Notifications\CampCreatedNotification;
use App\Notifications\CampDeletedNotification;
use App\Notifications\CampUpdatedNotification;
use App\Services\NotificationCenter;
use App\Support\ImportColumnMapper;
use App\Support\ImportSpreadsheetReader;
use App\Support\NotificationSections;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $this->markNotificationsRead(NotificationSections::CAMPS);

        $query = Camp::withCount('guardians');

        if (!auth()->user()->isAdmin()) {
            $query->where('id', auth()->user()->camp_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $camps        = $query->latest()->paginate(10);
        $totalCamps   = Camp::count();
        $activeCamps  = Camp::active()->count();
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
            'status'      => $request->is_active ? 'active' : 'inactive',
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'is_active'   => $request->is_active,
            'description' => $request->description,
            'created_by'  => auth()->id(),
            'current_occupancy' => 0,
        ]);

        app(NotificationCenter::class)->notifyAdmins(
            new CampCreatedNotification($request->name, $request->location)
        );

        return redirect()->route('camps.index')->with('success', 'تمت إضافة المخيم بنجاح');
    }

    public function update(Request $request, Camp $camp)
    {
        $this->authorizeCampAccess($camp->id);

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

        $currentPeople = $camp->guardians()->count()
            + $camp->guardians()->withCount('familyMembers')->get()->sum('family_members_count');

        if ((int) $request->capacity < $currentPeople) {
            throw ValidationException::withMessages([
                'capacity' => "لا يمكن جعل سعة المخيم ({$request->capacity}) أقل من الإشغال الحالي ({$currentPeople} شخص).",
            ]);
        }

        $camp->update([
            'name'        => $request->name,
            'location'    => $request->location,
            'manager'     => $request->manager,
            'phone'       => $request->phone,
            'capacity'    => $request->capacity,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'is_active'   => $request->is_active,
            'description' => $request->description,
        ]);

        $camp->updateOccupancy();

        app(NotificationCenter::class)->notifyAdmins(
            new CampUpdatedNotification($camp->name, $camp->location)
        );

        return redirect()->route('camps.index')->with('success', 'تم تحديث المخيم بنجاح');
    }

    public function destroy(Camp $camp)
    {
        $this->authorizeCampAccess($camp->id);

        $campName = $camp->name;
        $location = $camp->location;

        foreach ($camp->guardians as $guardian) {
            $guardian->familyMembers()->delete();
        }
        $camp->guardians()->delete();
        $camp->delete();

        app(NotificationCenter::class)->notifyAdmins(
            new CampDeletedNotification($campName, $location)
        );

        return back()->with('success', 'تم حذف المخيم وجميع عائلاته وأفرادها بنجاح.');
    }

    public function toggleStatus(Camp $camp)
    {
        if (!auth()->user()->canAccessCamp($camp->id)) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $camp->update(['is_active' => !$camp->is_active]);
        $camp->updateOccupancy();

        $status = $camp->is_active ? 'تم تفعيل المخيم' : 'تم تعليق المخيم';
        return back()->with('success', $status);
    }

    public function show(Camp $camp)
    {
        if (!auth()->user()->canAccessCamp($camp->id)) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        return response()->json($camp);
    }

    public function statistics(Camp $camp)
    {
        if (!auth()->user()->canAccessCamp($camp->id)) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $totalFamilies    = $camp->guardians()->count();
        $totalIndividuals = $totalFamilies + $camp->guardians()->sum('family_member_number');

        return response()->json([
            'total_families'    => $totalFamilies,
            'total_individuals' => $totalIndividuals,
            'camp'              => $camp,
        ]);
    }

    public function showImportForm()
    {
        return view('camp_management.camps_import');
    }

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

        $parsed = ImportSpreadsheetReader::read($request->file('file'));
        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        $dbFields = ImportColumnMapper::campFieldLabels();
        $autoMapping = ImportColumnMapper::mapCamps($headers);

        return view('camp_management.camps_import_map', compact('headers', 'rows', 'dbFields', 'autoMapping'));
    }

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
            $camp->updateOccupancy();
            $results['updated']++;
        } else {
            $data['current_occupancy'] = 0;
            $data['status'] = ($data['is_active'] ?? true) ? 'active' : 'inactive';
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
