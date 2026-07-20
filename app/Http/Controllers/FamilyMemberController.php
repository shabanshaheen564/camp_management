<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\FamilyMember;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleXLSX;

class FamilyMemberController extends Controller
{
    /**
     * جلب أفراد عائلة معينة
     * GET /api/guardians/{guardian}/members
     */
    public function byGuardian(Guardian $guardian)
    {
        if (auth()->user()->camp_id !== $guardian->camp_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        return response()->json($guardian->familyMembers()->get());
    }

    /**
     * إضافة فرد لعائلة
     * POST /api/family-members
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'guardian_id'  => 'required|integer|exists:guardians,id',
            'name'         => 'required|string|max:255',
            'card_id'      => 'nullable|string|max:50',
            'gender'       => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'relationship' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'nationality' => 'required|string|max:255',
        ]);

        $guardian = Guardian::findOrFail($data['guardian_id']);
        if (auth()->user()->camp_id !== $guardian->camp_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $member = FamilyMember::create($data);
        return response()->json($member, 201);
    }

    /**
     * حذف فرد من عائلة
     * DELETE /api/family-members/{member}
     */
    public function destroy(FamilyMember $member)
    {
        $guardian = Guardian::findOrFail($member->guardian_id);
        if (auth()->user()->camp_id !== $guardian->camp_id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $member->delete();
        return response()->json(['message' => 'تم الحذف']);
    }

    /**
     * Show members import form (redirects to families index since we use modal).
     */
    public function showImportForm()
    {
        return redirect()->route('families.index');
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
            'guardian_card_id' => 'رقم هوية رب الأسرة',
            'name' => 'اسم الفرد',
            'card_id' => 'رقم البطاقة',
            'gender' => 'الجنس',
            'date_of_birth' => 'تاريخ الميلاد',
            'nationality' => 'الجنسية',
            'relationship' => 'صلة القرابة',
            'phone_number' => 'الهاتف',
            'is_disabled' => 'ذوي الاحتياجات',
        ];

        return view('camp_management.members_import_map', compact('headers', 'rows', 'dbFields'));
    }

    /**
     * Execute import with column mapping.
     */
    public function importExecute(Request $request)
    {
        $request->validate([
            'mapping' => 'required|array',
            'import_rows' => 'required|string',
        ]);

        $rows = json_decode(base64_decode($request->input('import_rows', '')), true) ?: [];
        $mapping = $request->input('mapping', []);

        $guardianCardIdColumn = $mapping['guardian_card_id'] ?? null;
        $nameColumn = $mapping['name'] ?? null;

        if (!$guardianCardIdColumn) {
            return redirect()->route('families.index')->with('error', 'يرجى تحديد عمود رقم هوية رب الأسرة.');
        }

        if (!$nameColumn) {
            return redirect()->route('families.index')->with('error', 'يرجى تحديد عمود اسم الفرد.');
        }

        $guardianCardIds = [];
        foreach ($rows as $row) {
            $cardId = trim($row[$guardianCardIdColumn] ?? '');
            if ($cardId) {
                $guardianCardIds[] = $cardId;
            }
        }

        $existingGuardians = Guardian::whereIn('card_id', array_unique($guardianCardIds))->get()->keyBy('card_id');

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $guardianCardId = trim($row[$guardianCardIdColumn] ?? '');
            $name = trim($row[$nameColumn] ?? '');

            if (!$guardianCardId) {
                $results['errors'][] = "السطر " . ($index + 2) . ": رقم هوية رب الأسرة مفقود";
                continue;
            }

            if (!$name) {
                $results['errors'][] = "السطر " . ($index + 2) . ": اسم الفرد مفقود";
                continue;
            }

            $guardian = $existingGuardians->get($guardianCardId);

            if (!$guardian) {
                $results['errors'][] = "السطر " . ($index + 2) . ": رب الأسرة برقم هوية {$guardianCardId} غير موجود";
                continue;
            }

            $data = [
                'guardian_id' => $guardian->id,
                'name' => $name,
            ];

            foreach ($mapping as $dbField => $excelColumn) {
                if (in_array($dbField, ['guardian_card_id', 'name']) || !$excelColumn) continue;
                $value = trim($row[$excelColumn] ?? '');
                if ($value === '') continue;

                switch ($dbField) {
                    case 'gender':
                        $data[$dbField] = in_array(strtolower($value), ['male', 'ذكر']) ? 'male' : (in_array(strtolower($value), ['female', 'أنثى']) ? 'female' : null);
                        break;
                case 'date_of_birth':
                    try {
                        $data[$dbField] = \Carbon\Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $data[$dbField] = \Carbon\Carbon::parse($value)->format('Y-m-d');
                    }
                    break;
                    case 'is_disabled':
                        $data[$dbField] = in_array(strtolower($value), ['1', 'نعم', 'yes', 'true', 'نعم']);
                        break;
                    default:
                        $data[$dbField] = $value;
                }
            }

        $existingMember = null;
        if (!empty($data['card_id'])) {
            $existingMember = FamilyMember::where('card_id', $data['card_id'])->first();
        }

        if (!$existingMember) {
            $existingMember = FamilyMember::where('guardian_id', $guardian->id)
                ->where('name', $data['name'])
                ->first();
        }

        if ($existingMember) {
            $existingMember->update($data);
            $results['updated']++;
        } else {
            FamilyMember::create($data);
            $results['created']++;
        }
        }

        return redirect()->route('families.index')->with('success',
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }
}