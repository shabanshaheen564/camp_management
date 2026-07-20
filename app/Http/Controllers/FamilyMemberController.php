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
            'guardian_name' => 'اسم رب الأسرة',
            'guardian_marital_status' => 'الحالة الاجتماعية لرب الأسرة',
            'name' => 'اسم الفرد',
            'card_id' => 'رقم البطاقة',
            'gender' => 'الجنس',
            'date_of_birth' => 'تاريخ الميلاد',
            'nationality' => 'الجنسية',
            'relationship' => 'صلة القرابة',
            'phone_number' => 'الهاتف',
            'is_disabled' => 'ذوي الاحتياجات',
        ];

        $autoMapping = $this->buildAutoMapping($headers, $dbFields);

        $guardianCardIds = [];
        foreach ($rows as $row) {
            $cardId = trim((string) ($row[$autoMapping['guardian_card_id'] ?? ''] ?? ''));
            if ($cardId !== '') {
                $guardianCardIds[] = $cardId;
            }
        }

        $guardians = Guardian::withTrashed()->whereIn('card_id', array_unique($guardianCardIds))
            ->with('camp')
            ->get()
            ->keyBy('card_id');

        $newGuardianCardIds = array_diff(array_unique($guardianCardIds), $guardians->keys()->all());

        return view('camp_management.members_import_map', compact('headers', 'rows', 'dbFields', 'guardians', 'autoMapping', 'newGuardianCardIds'));
    }

    /**
     * Build automatic column mapping based on keyword similarity.
     */
    protected function buildAutoMapping(array $headers, array $dbFields): array
    {
        $mapping = [];
        $keywords = [
            'guardian_card_id' => ['guardian', 'card', 'id', 'هوية', 'رب الأسرة', 'رقم الهوية', 'parent', 'ولي الأمر'],
            'guardian_name' => ['guardian name', 'guardian', 'اسم رب الأسرة', 'ولي الأمر', 'parent name', 'اسم ولي الأمر', 'اسم رب العائلة'],
            'guardian_marital_status' => ['marital', 'حالة اجتماعية', 'متزوج', 'غير متزوج', 'social status', 'marital status'],
            'name' => ['name', 'الاسم', 'اسم الفرد', 'الاسم الكامل', 'fullname', 'full_name', 'first name', 'الاسم الاول'],
            'card_id' => ['card', 'بطاقة', 'رقم البطاقة', 'member id', 'member_card', 'كارت'],
            'gender' => ['gender', 'جنس', 'نوع', 'sex', 'male', 'female', 'ذكر', 'انثى'],
            'date_of_birth' => ['birth', 'dob', 'الميلاد', 'تاريخ الميلاد', 'date of birth', 'تاريخ'],
            'nationality' => ['nationality', 'جنسية', 'country', 'دولة'],
            'relationship' => ['relationship', 'صلة', 'قرابة', 'relation', ' Kinship'],
            'phone_number' => ['phone', 'هاتف', 'موبايل', 'mobile', 'tel', 'telephone', 'جوال'],
            'is_disabled' => ['disabled', 'احتياجات', 'disability', 'اعاقة', 'مقعد', 'special'],
        ];

        foreach ($dbFields as $field => $label) {
            $bestHeader = '';
            $bestScore = 0;

            foreach ($headers as $header) {
                $headerLower = mb_strtolower((string) $header, 'UTF-8');
                $score = 0;

                foreach ($keywords[$field] as $keyword) {
                    $keywordLower = mb_strtolower($keyword, 'UTF-8');
                    if ($headerLower === $keywordLower) {
                        $score += 10;
                    } elseif (str_contains($headerLower, $keywordLower)) {
                        $score += 5;
                    } elseif (str_contains($keywordLower, $headerLower)) {
                        $score += 3;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestHeader = $header;
                }
            }

            if ($bestScore >= 5) {
                $mapping[$field] = $bestHeader;
            }
        }

        return $mapping;
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
            if ($cardId !== '') {
                $guardianCardIds[] = $cardId;
            }
        }

        $existingGuardians = Guardian::withTrashed()->whereIn('card_id', array_unique($guardianCardIds))->get()->keyBy('card_id');

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            try {
                $this->processMemberRow($row, $mapping, $guardianCardIdColumn, $nameColumn, $existingGuardians, $results);
            } catch (\Throwable $e) {
                $results['errors'][] = "السطر " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return redirect()->route('families.index')->with('success',
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }

    protected function processMemberRow(array $row, array $mapping, ?string $guardianCardIdColumn, ?string $nameColumn, $existingGuardians, array &$results): void
    {
        $guardianCardId = trim((string) ($row[$guardianCardIdColumn] ?? ''));
        $name = trim((string) ($row[$nameColumn] ?? ''));

        if ($guardianCardId === '') {
            throw new \InvalidArgumentException('رقم هوية رب الأسرة مفقود');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('اسم الفرد مفقود');
        }

        $guardian = $existingGuardians->get($guardianCardId);

        if (!$guardian) {
            $guardianName = trim((string) ($row[$mapping['guardian_name'] ?? ''] ?? ''));
            if ($guardianName === '') {
                $guardianName = 'رب أسرة ' . $guardianCardId;
            }

            $guardianMaritalStatus = trim((string) ($row[$mapping['guardian_marital_status'] ?? ''] ?? ''));
            if ($guardianMaritalStatus === '' || !in_array(strtolower($guardianMaritalStatus), ['married', 'متزوج'])) {
                $guardianMaritalStatus = 'single';
            } else {
                $guardianMaritalStatus = 'married';
            }

            $defaultCamp = \App\Models\Camp::where('is_active', true)->first();

            $guardian = Guardian::create([
                'camp_id' => $defaultCamp?->id,
                'card_id' => $guardianCardId,
                'first_name' => $guardianName,
                'second_name' => '',
                'third_name' => '',
                'family_name' => '',
                'date_of_birth' => '1900-01-01',
                'gender' => 'male',
                'marital_status' => $guardianMaritalStatus,
                'nationality' => 'فلسطيني',
                'family_member_number' => 0,
                'is_disabled' => 0,
            ]);

            $existingGuardians->put($guardianCardId, $guardian);
            $results['created']++;
        }

        $data = [
            'guardian_id' => $guardian->id,
            'name' => $name,
            'marital_status' => $guardian->marital_status === 'married' ? 'married' : 'single',
        ];

        foreach ($mapping as $dbField => $excelColumn) {
            if (in_array($dbField, ['guardian_card_id', 'guardian_name', 'guardian_marital_status', 'name']) || !$excelColumn) continue;
            $rawValue = $row[$excelColumn] ?? '';
            $value = $this->normalizeExcelValue($rawValue, $dbField);
            if ($value === null || $value === '') continue;

            $data[$dbField] = $value;
        }

        $memberCardId = $data['card_id'] ?? null;

        if ($memberCardId !== null && $memberCardId !== '') {
            $existingMember = FamilyMember::where('card_id', $memberCardId)->first();
            if ($existingMember) {
                $existingMember->update($data);
                $results['updated']++;
                return;
            }
        }

        FamilyMember::create($data);
        $results['created']++;
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
            'gender' => $this->normalizeGender($value),
            'date_of_birth' => $this->normalizeDate($value),
            'is_disabled' => $this->normalizeDisabled($value),
            default => (string) $value,
        };
    }

    protected function normalizeGender(string $value): ?string
    {
        $normalized = mb_strtolower($value, 'UTF-8');
        return match (true) {
            in_array($normalized, ['male', 'ذكر', 'm']) => 'male',
            in_array($normalized, ['female', 'أنثى', 'f']) => 'female',
            default => null,
        };
    }

    protected function normalizeDate(string $value): ?string
    {
        if ($value === '') return null;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeDisabled(string $value): bool
    {
        $normalized = mb_strtolower($value, 'UTF-8');
        return in_array($normalized, ['1', 'نعم', 'yes', 'true', 'disabled', 'ذوي الاحتياجات']);
    }
}