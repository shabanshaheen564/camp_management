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
use App\Support\ImportColumnMapper;
use App\Support\ImportSpreadsheetReader;

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

        $parsed = ImportSpreadsheetReader::read($request->file('file'));
        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        if (empty($headers) || empty($rows)) {
            return response()->json(['message' => 'الملف فارغ أو تعذّرت قراءته'], 422);
        }

        $dbFields = ImportColumnMapper::memberFieldLabels();
        $autoMapping = ImportColumnMapper::mapMembers($headers);
        $guardianCardIds = $this->collectGuardianCardIds($rows, $autoMapping);

        $guardians = Guardian::withTrashed()
            ->whereIn('card_id', $guardianCardIds)
            ->with('camp')
            ->get()
            ->keyBy('card_id');

        return response()->json([
            'headers' => array_values($headers),
            'rows' => $rows,
            'db_fields' => $dbFields,
            'auto_mapping' => $autoMapping,
            'new_guardian_card_ids' => array_values(array_diff($guardianCardIds, $guardians->keys()->all())),
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

        $parsed = ImportSpreadsheetReader::read($request->file('file'));
        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        $dbFields = ImportColumnMapper::memberFieldLabels();
        $autoMapping = ImportColumnMapper::mapMembers($headers);
        $guardianCardIds = $this->collectGuardianCardIds($rows, $autoMapping);

        $guardians = Guardian::withTrashed()
            ->whereIn('card_id', $guardianCardIds)
            ->with('camp')
            ->get()
            ->keyBy('card_id');

        $newGuardianCardIds = array_values(array_diff($guardianCardIds, $guardians->keys()->all()));

        return view('camp_management.members_import_map', compact(
            'headers', 'rows', 'dbFields', 'guardians', 'autoMapping', 'newGuardianCardIds'
        ));
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

        $existingGuardians = Guardian::withTrashed()
            ->whereIn('card_id', array_unique($guardianCardIds))
            ->get()
            ->keyBy('card_id');

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

    /**
     * @return array<int, string>
     */
    protected function collectGuardianCardIds(array $rows, array $mapping): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $cardId = trim((string) ($row[$mapping['guardian_card_id'] ?? ''] ?? ''));
            if ($cardId !== '') {
                $ids[] = $cardId;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveCampFromImport(string $campNameOrId, ?int $forcedCampId = null): Camp
    {
        if ($forcedCampId !== null) {
            $camp = Camp::find($forcedCampId);
            if (!$camp) {
                throw new \InvalidArgumentException('المخيم غير موجود');
            }

            return $camp;
        }

        $campNameOrId = trim($campNameOrId);
        if ($campNameOrId === '') {
            throw new \InvalidArgumentException('اسم المخيم مفقود');
        }

        $camp = Camp::active()->where('name', $campNameOrId)->first();

        if (!$camp) {
            $camp = Camp::active()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($campNameOrId, 'UTF-8')])
                ->first();
        }

        if (!$camp && is_numeric($campNameOrId)) {
            $camp = Camp::active()->find((int) $campNameOrId);
        }

        if (!$camp) {
            throw new \InvalidArgumentException('المخيم غير موجود: ' . $campNameOrId);
        }

        return $camp;
    }

    protected function isGuardianRow(array $row, array $mapping, string $guardianCardId, string $memberCardId): bool
    {
        if ($guardianCardId === '') {
            return false;
        }

        if ($memberCardId !== '' && $memberCardId === $guardianCardId) {
            return true;
        }

        $relationship = mb_strtolower(trim((string) ($row[$mapping['relationship'] ?? ''] ?? '')), 'UTF-8');
        $markers = [
            'رب الأسرة', 'رب اسرة', 'رب العائلة', 'ولي الأمر', 'ولي الامر',
            'head', 'guardian', 'household', 'self', 'نفسه', 'رب الاسرة',
        ];

        foreach ($markers as $marker) {
            if ($relationship !== '' && str_contains($relationship, mb_strtolower($marker, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    protected function buildGuardianPayloadFromRow(
        array $row,
        array $mapping,
        string $cardId,
        string $name,
        int $campId,
        bool $preferGuardianColumns = true
    ): array {
        if ($name === '') {
            $name = trim((string) ($row[$mapping['guardian_name'] ?? ''] ?? ''));
        }
        if ($name === '') {
            $name = 'رب أسرة ' . $cardId;
        }

        $nameParts = $this->parseFullName($name);
        $maritalColumn = $preferGuardianColumns ? ($mapping['guardian_marital_status'] ?? null) : null;
        $maritalRaw = trim((string) ($row[$maritalColumn ?? ''] ?? ''));
        if ($maritalRaw === '' && !empty($mapping['marital_status'])) {
            $maritalRaw = trim((string) ($row[$mapping['marital_status']] ?? ''));
        }

        $payload = [
            'camp_id'              => $campId,
            'card_id'              => $cardId,
            'first_name'           => $nameParts['first_name'],
            'second_name'          => $nameParts['second_name'],
            'third_name'           => $nameParts['third_name'],
            'family_name'          => $nameParts['family_name'],
            'nationality'          => 'فلسطيني',
            'family_member_number' => 0,
            'is_disabled'          => 0,
            'marital_status'       => $this->normalizeMaritalStatus($maritalRaw ?: 'single'),
            'gender'               => $this->normalizeExcelValue($row[$mapping['gender'] ?? ''] ?? '', 'gender') ?? 'male',
            'date_of_birth'        => $this->normalizeExcelValue($row[$mapping['date_of_birth'] ?? ''] ?? '', 'date_of_birth') ?? '1900-01-01',
        ];

        $phone = trim((string) ($row[$mapping['phone_number'] ?? ''] ?? ''));
        if ($phone !== '') {
            $payload['phone'] = $phone;
        }

        $nationality = trim((string) ($row[$mapping['nationality'] ?? ''] ?? ''));
        if ($nationality !== '') {
            $payload['nationality'] = $nationality;
        }

        $disabled = $this->normalizeExcelValue($row[$mapping['is_disabled'] ?? ''] ?? '', 'is_disabled');
        if ($disabled !== null) {
            $payload['is_disabled'] = $disabled ? 1 : 0;
        }

        return $payload;
    }

    protected function upsertGuardianRecord(
        array $row,
        array $mapping,
        string $guardianCardId,
        string $displayName,
        Camp $camp,
        $existingGuardians,
        array &$results,
        array &$newGuardians,
        bool $preferGuardianColumns = true
    ): Guardian {
        $payload = $this->buildGuardianPayloadFromRow(
            $row,
            $mapping,
            $guardianCardId,
            $displayName,
            $camp->id,
            $preferGuardianColumns
        );

        /** @var Guardian|null $guardian */
        $guardian = $existingGuardians->get($guardianCardId);

        if ($guardian) {
            if ($guardian->trashed()) {
                $guardian->restore();
            }

            $guardian->update($payload);
            $results['updated']++;
        } else {
            $guardian = Guardian::create($payload);
            $existingGuardians->put($guardianCardId, $guardian);
            $results['created']++;
            $newGuardians[] = $guardian;
        }

        return $guardian->fresh();
    }

    protected function processMemberRow(
        array $row,
        array $mapping,
        ?string $guardianCardIdColumn,
        ?string $nameColumn,
        ?string $campColumn,
        $existingGuardians,
        array &$results,
        array &$newGuardians = [],
        ?int $forcedCampId = null
    ): void {
        $guardianCardId = trim((string) ($row[$guardianCardIdColumn ?? ''] ?? ''));
        $name = trim((string) ($row[$nameColumn ?? ''] ?? ''));
        $memberCardId = trim((string) ($row[$mapping['card_id'] ?? ''] ?? ''));

        if ($guardianCardId === '') {
            throw new \InvalidArgumentException('رقم هوية رب الأسرة / ولي الأمر مفقود');
        }

        $campNameOrId = trim((string) ($row[$campColumn ?? ''] ?? ''));
        $camp = $this->resolveCampFromImport($campNameOrId, $forcedCampId);
        $isGuardianRow = $this->isGuardianRow($row, $mapping, $guardianCardId, $memberCardId);

        if ($isGuardianRow) {
            $guardianName = $name !== '' ? $name : trim((string) ($row[$mapping['guardian_name'] ?? ''] ?? ''));
            $this->upsertGuardianRecord(
                $row,
                $mapping,
                $guardianCardId,
                $guardianName,
                $camp,
                $existingGuardians,
                $results,
                $newGuardians,
                true
            );

            return;
        }

        if ($name === '') {
            throw new \InvalidArgumentException('اسم الفرد مفقود');
        }

        $guardian = $existingGuardians->get($guardianCardId);

        if (!$guardian) {
            $guardianName = trim((string) ($row[$mapping['guardian_name'] ?? ''] ?? ''));
            $guardian = $this->upsertGuardianRecord(
                $row,
                $mapping,
                $guardianCardId,
                $guardianName,
                $camp,
                $existingGuardians,
                $results,
                $newGuardians,
                true
            );
        } elseif ((int) $guardian->camp_id !== (int) $camp->id) {
            if ($guardian->trashed()) {
                $guardian->restore();
            }
            $guardian->update(['camp_id' => $camp->id]);
            $guardian = $guardian->fresh();
            $existingGuardians->put($guardianCardId, $guardian);
            $results['updated']++;
        }

        $data = [
            'guardian_id' => $guardian->id,
            'name'        => $name,
        ];

        foreach ($mapping as $dbField => $excelColumn) {
            if (in_array($dbField, [
                'guardian_card_id', 'guardian_name', 'guardian_marital_status', 'guardian_camp', 'name',
            ], true) || !$excelColumn) {
                continue;
            }

            $rawValue = $row[$excelColumn] ?? '';
            $value = $this->normalizeExcelValue($rawValue, $dbField);
            if ($value === null || $value === '') {
                continue;
            }

            $data[$dbField] = $value;
        }

        if (empty($data['marital_status'])) {
            $rawMarital = '';

            if (!empty($mapping['marital_status'])) {
                $rawMarital = trim((string) ($row[$mapping['marital_status']] ?? ''));
            } elseif (!empty($mapping['guardian_marital_status'])) {
                $rawMarital = trim((string) ($row[$mapping['guardian_marital_status']] ?? ''));
            }

            $data['marital_status'] = $rawMarital !== ''
                ? $this->normalizeMaritalStatus($rawMarital)
                : (in_array($guardian->marital_status, ['married', 'divorced', 'widowed'], true)
                    ? $guardian->marital_status
                    : 'single');
        }

        if ($memberCardId !== '') {
            $existingMember = FamilyMember::where('card_id', $memberCardId)->first();
            if ($existingMember) {
                $existingMember->update($data);
                $results['updated']++;

                return;
            }

            $data['card_id'] = $memberCardId;
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
            'gender'         => $this->normalizeGender($value),
            'date_of_birth'  => $this->normalizeDate($value),
            'is_disabled'    => $this->normalizeDisabled($value),
            'marital_status' => $this->normalizeMaritalStatus((string) $value),
            default          => (string) $value,
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