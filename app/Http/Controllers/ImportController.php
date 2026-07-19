<?php

namespace App\Http\Controllers;

use App\Imports\CampsImport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function showImportForm()
    {
        return view('camp_management.camps_import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
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
            if ($xlsx = \SimpleXLSX::parse($path)) {
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

        session(['import_file' => $path, 'import_extension' => $file->getClientOriginalExtension()]);

        return view('camp_management.camps_import_map', compact('headers', 'rows', 'dbFields'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'mapping' => 'required|array',
        ]);

        $path = session('import_file');
        $extension = session('import_extension', 'xlsx');

        if (!$path || !file_exists($path)) {
            return redirect()->route('camps.import.form')->with('error', 'انتهت صلاحية الملف. يرجى رفعه مرة أخرى.');
        }

        $mapping = $request->input('mapping', []);

        $nameColumn = $mapping['name'] ?? null;
        if (!$nameColumn) {
            return redirect()->route('camps.import.form')->with('error', 'يرجى تحديد عمود اسم المخيم.');
        }

        $rows = [];
        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $headers = fgetcsv($handle, 0, ',');
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rows[] = array_combine($headers, $row);
                }
                fclose($handle);
            }
        } else {
            if ($xlsx = \SimpleXLSX::parse($path)) {
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

        session()->forget(['import_file', 'import_extension']);

        return redirect()->route('camps.index')->with('success', 
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }
}
