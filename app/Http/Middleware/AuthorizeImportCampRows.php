<?php

namespace App\Http\Middleware;

use App\Models\Camp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeImportCampRows
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin can import into any camp.
        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        $mapping = $request->input('mapping', []);
        $campColumn = $mapping['guardian_camp'] ?? null;

        if (!$campColumn) {
            return $this->deny($request, ['غير مصرح: يجب تحديد عمود اسم المخيم.']);
        }

        $rows = $this->extractRows($request);
        $allowedRows = [];
        $warnings = [];

        foreach ($rows as $index => $row) {
            $campName = trim((string) ($row[$campColumn] ?? ''));
            $familyName = $this->familyName($row, $mapping);

            if ($campName === '') {
                $warnings[] = 'السطر ' . ($index + 2) . ': غير مصرح للعائلة: ' . $familyName . ' — اسم المخيم مفقود.';
                continue;
            }

            $camp = $this->resolveCamp($campName);

            if (!$camp) {
                $warnings[] = 'السطر ' . ($index + 2) . ': غير مصرح للعائلة: ' . $familyName . ' — المخيم غير موجود: ' . $campName;
                continue;
            }

            if ((int) $camp->id !== (int) $user->camp_id) {
                $warnings[] = 'السطر ' . ($index + 2) . ': غير مصرح للعائلة: ' . $familyName . ' — المخيم المحدد: ' . $camp->name;
                continue;
            }

            $allowedRows[] = $row;
        }

        $this->replaceRows($request, $allowedRows);

        $response = $next($request);

        if (!empty($warnings)) {
            $this->attachWarnings($request, $response, $warnings);
        }

        return $response;
    }

    protected function extractRows(Request $request): array
    {
        if ($request->has('rows')) {
            return is_array($request->input('rows')) ? $request->input('rows') : [];
        }

        $encoded = (string) $request->input('import_rows', '');
        if ($encoded === '') {
            return [];
        }

        $decoded = json_decode(base64_decode($encoded), true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function replaceRows(Request $request, array $rows): void
    {
        if ($request->has('rows')) {
            $request->merge(['rows' => $rows]);
            return;
        }

        $request->merge([
            'import_rows' => base64_encode(json_encode($rows, JSON_UNESCAPED_UNICODE)),
        ]);
    }

    protected function resolveCamp(string $name): ?Camp
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($name));

        return Camp::active()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($normalized, 'UTF-8')])
            ->first();
    }

    protected function familyName(array $row, array $mapping): string
    {
        foreach (['guardian_name', 'name'] as $field) {
            $column = $mapping[$field] ?? null;
            if ($column) {
                $value = trim((string) ($row[$column] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $cardColumn = $mapping['guardian_card_id'] ?? null;
        $cardId = trim((string) ($row[$cardColumn ?? ''] ?? ''));

        return $cardId !== '' ? 'صاحب الهوية ' . $cardId : 'غير معروفة';
    }

    protected function attachWarnings(Request $request, Response $response, array $warnings): void
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            $data['warnings'] = array_values(array_merge($data['warnings'] ?? [], $warnings));
            $data['skipped_unauthorized'] = count($warnings);
            $response->setData($data);
            return;
        }

        if ($request->hasSession()) {
            $existing = $request->session()->get('import_errors', []);
            $request->session()->flash('import_errors', array_values(array_merge($existing, $warnings)));
        }
    }

    protected function deny(Request $request, array $messages): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'authorized' => false,
                'message' => 'غير مصرح',
                'errors' => $messages,
            ], 200);
        }

        return redirect()->back()->with('error', $messages[0]);
    }
}
