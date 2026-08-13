<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\FamilyMember;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use SimpleXLSX;
use Shuchkin\SimpleXLSXGen;
use App\Notifications\FamilyCreatedNotification;

class FamilyMemberController extends Controller
{
    /**
     * جلب أفراد عائلة معينة
     * GET /api/guardians/{guardian}/members
     */
    public function byGuardian(Guardian $guardian)
    {
        $this->authorizeGuardianAccess($guardian, expectsJson: true);

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
            'relationship' => 'nullable|string|max:255',
            'card_id'      => 'nullable|string|max:50',
            'gender'       => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:20',
            'nationality' => 'required|string|max:255',
            'marital_status' => 'nullable|in:single,married,divorced,widowed', // ✅ جديد
            'is_disabled' => 'nullable|boolean', // ✅ جديد (كان ناقص - عشان هيك سويتش "من ذوي الإعاقة" بالتطبيق ما كان بيتحفظ)
        ]);

        $guardian = Guardian::findOrFail($data['guardian_id']);
        $this->authorizeGuardianAccess($guardian, expectsJson: true);

        $member = FamilyMember::create($data);
        return response()->json($member, 201);
    }

    /**
     * حذف فرد من عائلة
     * DELETE /api/family-members/{member}
     */
    public function destroy(FamilyMember $member)
    {
        $this->authorizeFamilyMemberAccess($member, expectsJson: true);

        $member->delete();
        return response()->json(['message' => 'تم الحذف']);
    }

    /**
     * استيراد ملف إكسل/CSV من التطبيق (خطوة واحدة، بدون تدخل يدوي
     * لمطابقة الأعمدة كما بالويب - نعتمد على buildAutoMapping تلقائياً).
     * أي عائلة/فرد جديد بيتسجل حصراً بمخيم اليوزر الحالي، بغض النظر
     * عن أي عمود "مخيم" موجود بالملف نفسه (لأسباب أمنية).
     *
     * POST /api/camps/{camp}/guardians/import
     */
    /**
     * الخطوة 1: رفع الملف وقراءته وإرجاع الأعمدة + اقتراح تلقائي للمطابقة
     * (بدون حفظ أي شي بقاعدة البيانات بعد) - نفس منطق importPreview
     * بالويب بالضبط، بس بيرجع JSON للتطبيق بدل صفحة Blade.
     *
     * POST /api/camps/{camp}/guardians/import/preview
     */
    public function apiImportPreview(Request $request, Camp $camp)
    {
        $this->authorizeCampAccess($camp->id, expectsJson: true);

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
                $allRows = $xlsx->rows();
                $headers = array_shift($allRows);
                foreach ($allRows as $row) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        if (empty($headers) || empty($rows)) {
            return response()->json(['message' => 'الملف فارغ أو تعذّرت قراءته'], 422);
        }

        // ملاحظة: guardian_camp مش موجود هون قصداً، لأنه بالموبايل المخيم
        // محدد سلفاً (مخيم اليوزر الحالي) ومش قابل للتغيير من الملف.
        $dbFields = [
            'guardian_card_id' => 'رقم هوية رب الأسرة',
            'guardian_name' => 'اسم رب الأسرة',
            'guardian_marital_status' => 'الحالة الاجتماعية لرب الأسرة',
            'name' => 'اسم الفرد',
            'card_id' => 'رقم البطاقة',
            'gender' => 'الجنس',
            'date_of_birth' => 'تاريخ الميلاد',
            'nationality' => 'الجنسية',
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

        $guardians = Guardian::withTrashed()
            ->whereIn('card_id', array_unique($guardianCardIds))
            ->where('camp_id', $camp->id)
            ->get()
            ->keyBy('card_id');

        $newGuardianCardIds = array_values(array_diff(array_unique($guardianCardIds), $guardians->keys()->all()));

        return response()->json([
            'headers' => array_values($headers),
            'rows' => $rows,
            'db_fields' => $dbFields,
            'auto_mapping' => $autoMapping,
            'new_guardian_card_ids' => $newGuardianCardIds,
            'existing_guardian_card_ids' => $guardians->keys()->values(),
            'total_rows' => count($rows),
        ]);
    }

    /**
     * الخطوة 2: تنفيذ الاستيراد الفعلي بعد ما المستخدم يأكّد/يعدّل المطابقة
     * بالتطبيق - نفس منطق importExecute بالويب بالضبط.
     *
     * POST /api/camps/{camp}/guardians/import/execute
     * body: { mapping: {field: header}, rows: [...] }
     */
    public function apiImportExecute(Request $request, Camp $camp)
    {
        $this->authorizeCampAccess($camp->id, expectsJson: true);

        $data = $request->validate([
            'mapping' => 'required|array',
            'rows' => 'required|array',
        ]);

        $mapping = $data['mapping'];
        $rows = $data['rows'];

        $guardianCardIdColumn = $mapping['guardian_card_id'] ?? null;
        $nameColumn = $mapping['name'] ?? null;

        if (!$guardianCardIdColumn) {
            return response()->json(['message' => 'يرجى تحديد عمود رقم هوية رب الأسرة'], 422);
        }
        if (!$nameColumn) {
            return response()->json(['message' => 'يرجى تحديد عمود اسم الفرد'], 422);
        }

        $guardianCardIds = [];
        foreach ($rows as $row) {
            $cardId = trim((string) ($row[$guardianCardIdColumn] ?? ''));
            if ($cardId !== '') {
                $guardianCardIds[] = $cardId;
            }
        }

        $existingGuardians = Guardian::withTrashed()
            ->whereIn('card_id', array_unique($guardianCardIds))
            ->where('camp_id', $camp->id)
            ->get()
            ->keyBy('card_id');

        $results = ['created' => 0, 'updated' => 0, 'errors' => []];
        $newGuardians = [];

        foreach ($rows as $index => $row) {
            try {
                $this->processMemberRow($row, $mapping, $guardianCardIdColumn, $nameColumn, null, $existingGuardians, $results, $newGuardians, $camp->id);
            } catch (\Throwable $e) {
                $results['errors'][] = 'السطر ' . ($index + 2) . ': ' . $e->getMessage();
            }
        }

        if (!empty($newGuardians)) {
            $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
            foreach ($newGuardians as $guardian) {
                foreach ($admins as $admin) {
                    $admin->notify(new FamilyCreatedNotification($guardian->full_name ?? $guardian->first_name, $guardian->camp?->name, $guardian->card_id));
                }
            }
        }

        return response()->json([
            'created' => $results['created'],
            'updated' => $results['updated'],
            'errors' => $results['errors'],
            'message' => "تم الاستيراد: {$results['created']} جديد، {$results['updated']} محدّث" . (count($results['errors']) ? '، مع ' . count($results['errors']) . ' خطأ' : ''),
        ]);
    }

    /**
     * تصدير أفراد مخيم اليوزر الحالي كملف CSV (يفتح مباشرة على Excel).
     * نفس نمط ReportController::exportMembers بالضبط، بس محصور بمخيم
     * اليوزر تلقائياً (بدون إمكانية تصدير مخيم تاني عبر الموبايل).
     *
     * GET /api/camps/{camp}/guardians/export
     */
   public function apiExport(Camp $camp)
{
    $this->authorizeCampAccess($camp->id, expectsJson: true);

    $members = FamilyMember::whereHas('guardian', function ($q) use ($camp) {
        $q->where('camp_id', $camp->id);
    })->with('guardian')->orderBy('name')->get();

    $filename = 'members_' . str_replace(' ', '_', $camp->name) . '_' . now()->format('Ymd_His') . '.xlsx';

    $rows = [
        ['ID', 'الاسم', 'رقم البطاقة', 'الجنس', 'تاريخ الميلاد', 'الجنسية', 'الهاتف', 'ذوي الإعاقة', 'الحالة الاجتماعية', 'رب الأسرة', 'رقم هوية رب الأسرة'],
    ];

    foreach ($members as $member) {
        $rows[] = [
            $member->id,
            $member->name,
            $member->card_id ?? '',
            $member->gender ?? '',
            optional($member->date_of_birth)->format('Y-m-d') ?? '',
            $member->nationality ?? '',
            $member->phone_number ?? '',
            $member->is_disabled ? 'نعم' : 'لا',
            $member->marital_status ?? '',
            $member->guardian?->full_name ?? '',
            $member->guardian?->card_id ?? '',
        ];
    }

    $xlsx = SimpleXLSXGen::fromArray($rows);

    return response((string) $xlsx, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
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
                $allRows = $xlsx->rows();
                $headers = array_shift($allRows);
                foreach ($allRows as $row) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        $dbFields = [
            'guardian_card_id' => 'رقم هوية رب الأسرة',
            'guardian_name' => 'اسم رب الأسرة',
            'guardian_marital_status' => 'الحالة الاجتماعية لرب الأسرة',
            'guardian_camp' => 'اسم المخيم',
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

        $cardIdToMaritalStatus = [];
        foreach ($rows as $row) {
            $rowCardId = trim((string) ($row[$autoMapping['guardian_card_id'] ?? ''] ?? ''));
            if ($rowCardId !== '') {
                $maritalStatus = trim((string) ($row[$autoMapping['guardian_marital_status'] ?? ''] ?? ''));
                $cardIdToMaritalStatus[$rowCardId] = $this->normalizeMaritalStatus($maritalStatus);
            }
        }

        $filteredNewGuardianCardIds = [];
        foreach ($newGuardianCardIds as $cardId) {
            $status = $cardIdToMaritalStatus[$cardId] ?? 'single';
            if (in_array($status, ['married', 'divorced', 'widowed'])) {
                $filteredNewGuardianCardIds[] = $cardId;
            }
        }
        $newGuardianCardIds = array_values(array_unique($filteredNewGuardianCardIds));

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
            'guardian_marital_status' => ['marital', 'حالة اجتماعية', 'متزوج', 'غير متزوج', 'social status', 'marital status', 'أرمل', 'مطلق', 'أعزب', 'widowed', 'divorced', 'single', 'separated', 'منفصل'],
            'guardian_camp' => ['camp', 'مخيم', 'اسم المخيم', 'المخيم', 'camp name', 'location'],
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
            $isGuardianField = str_starts_with($field, 'guardian_');
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

                // حقول رب الأسرة (guardian_*) ممكن تتشابه أعمدتها مع عمود عام
                // بيخص الفرد نفسه (مثلاً Marital Status و Guardian Marital Status
                // بنفس الملف). لو العمود فيه إشارة صريحة إنه خاص برب الأسرة،
                // منفضّله على أي عمود عام حتى لو أخد نقاط أعلى بالتطابق الحرفي.
                if ($isGuardianField && $score > 0) {
                    $hasGuardianMarker = str_contains($headerLower, 'guardian')
                        || str_contains($headerLower, 'رب الأسرة')
                        || str_contains($headerLower, 'رب أسرة')
                        || str_contains($headerLower, 'ولي الأمر')
                        || str_contains($headerLower, 'parent');
                    if ($hasGuardianMarker) {
                        $score += 8;
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
        $campColumn = $mapping['guardian_camp'] ?? null;

        if (!$guardianCardIdColumn) {
            return redirect()->route('families.index')->with('error', 'يرجى تحديد عمود رقم هوية رب الأسرة.');
        }

        if (!$nameColumn) {
            return redirect()->route('families.index')->with('error', 'يرجى تحديد عمود اسم الفرد.');
        }

        if (!$campColumn) {
            return redirect()->route('families.index')->with('error', 'يرجى تحديد عمود اسم المخيم.');
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
        $newGuardians = [];

        foreach ($rows as $index => $row) {
            try {
                $this->processMemberRow($row, $mapping, $guardianCardIdColumn, $nameColumn, $campColumn, $existingGuardians, $results, $newGuardians);
            } catch (\Throwable $e) {
                $results['errors'][] = "السطر " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        if (!empty($newGuardians)) {
            $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
            foreach ($newGuardians as $guardian) {
                foreach ($admins as $admin) {
                    $admin->notify(new FamilyCreatedNotification($guardian->full_name ?? $guardian->first_name, $guardian->camp?->name, $guardian->card_id));
                }
            }
        }

        return redirect()->route('families.index')->with('success',
            "تم الاستيراد بنجاح: {$results['created']} جديد، {$results['updated']} محدث."
        )->with('import_errors', $results['errors']);
    }

    protected function processMemberRow(array $row, array $mapping, ?string $guardianCardIdColumn, ?string $nameColumn, ?string $campColumn, $existingGuardians, array &$results, array &$newGuardians = [], ?int $forcedCampId = null): void
    {
        $guardianCardId = trim((string) ($row[$guardianCardIdColumn] ?? ''));
        $name = trim((string) ($row[$nameColumn] ?? ''));

        if ($guardianCardId === '') {
            throw new \InvalidArgumentException('رقم هوية رب الأسرة مفقود');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('اسم الفرد مفقود');
        }

        $guardianMaritalStatusRaw = trim((string) ($row[$mapping['guardian_marital_status'] ?? ''] ?? ''));
        $guardianMaritalStatus = $this->normalizeMaritalStatus($guardianMaritalStatusRaw);
        $isGuardian = in_array($guardianMaritalStatus, ['married', 'divorced', 'widowed']);

        $guardian = $existingGuardians->get($guardianCardId);

        if (!$guardian && $isGuardian) {
            $guardianName = trim((string) ($row[$mapping['guardian_name'] ?? ''] ?? ''));
            if ($guardianName === '') {
                $guardianName = 'رب أسرة ' . $guardianCardId;
            }

            $campNameOrId = trim((string) ($row[$campColumn ?? ''] ?? ''));

            if ($forcedCampId !== null) {
                // استيراد من الموبايل: المخيم محدد سلفاً (مخيم اليوزر الحالي)
                // بغض النظر عن أي عمود مخيم موجود بالملف، لأسباب أمنية
                // (ما بنسمح لمدير مخيم يستورد بيانات لمخيم تاني).
                $camp = Camp::find($forcedCampId);
            } else {
                if ($campNameOrId === '') {
                    throw new \InvalidArgumentException('اسم المخيم مفقود');
                }

                $camp = Camp::where('is_active', true)->where(function ($q) use ($campNameOrId) {
                    $q->where('name', $campNameOrId);
                    if (is_numeric($campNameOrId)) {
                        $q->orWhere('id', $campNameOrId);
                    }
                })->first();
            }

            if (!$camp) {
                throw new \InvalidArgumentException('المخيم غير موجود: ' . $campNameOrId);
            }

            $guardian = Guardian::create([
                'camp_id' => $camp->id,
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
            $newGuardians[] = $guardian;
        } elseif (!$guardian && !$isGuardian) {
            throw new \InvalidArgumentException('رب الأسرة غير موجود: ' . $guardianCardId);
        }

        $data = [
            'guardian_id' => $guardian->id,
            'name' => $name,
            'marital_status' => in_array($guardian->marital_status, ['married', 'divorced', 'widowed']) ? $guardian->marital_status : 'single',
        ];

        foreach ($mapping as $dbField => $excelColumn) {
            if (in_array($dbField, ['guardian_card_id', 'guardian_name', 'guardian_marital_status', 'guardian_camp', 'name']) || !$excelColumn) continue;
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

    protected function normalizeMaritalStatus(string $value): string
    {
        $normalized = mb_strtolower(trim($value), 'UTF-8');

        return match (true) {
            in_array($normalized, ['single', 'غير متزوج', 'أعزب', 'never married']) => 'single',
            in_array($normalized, ['married', 'متزوج']) => 'married',
            in_array($normalized, ['divorced', 'مطلق']) => 'divorced',
            in_array($normalized, ['widowed', 'أرمل']) => 'widowed',
            in_array($normalized, ['separated', 'منفصل']) => 'divorced',
            default => 'single',
        };
    }
}