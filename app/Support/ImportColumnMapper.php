<?php

namespace App\Support;

class ImportColumnMapper
{
    public static function campFieldLabels(): array
    {
        return [
            'name'              => 'اسم المخيم',
            'location'          => 'الموقع',
            'latitude'          => 'خط العرض',
            'longitude'         => 'خط الطول',
            'capacity'          => 'الطاقة الاستيعابية',
            'current_occupancy' => 'الإشغال الحالي',
            'manager'           => 'مدير المخيم',
            'phone'             => 'الهاتف',
            'description'       => 'الوصف',
            'status'            => 'الحالة',
            'is_active'         => 'نشط',
        ];
    }

    public static function memberFieldLabels(): array
    {
        return [
            'guardian_card_id'        => 'رقم هوية رب الأسرة / ولي الأمر',
            'guardian_name'           => 'اسم رب الأسرة',
            'guardian_marital_status' => 'الحالة الاجتماعية لرب الأسرة',
            'guardian_camp'           => 'اسم المخيم',
            'name'                    => 'اسم الفرد',
            'card_id'                 => 'رقم البطاقة / الهوية',
            'gender'                  => 'الجنس',
            'date_of_birth'           => 'تاريخ الميلاد',
            'nationality'             => 'الجنسية',
            'marital_status'          => 'الحالة الاجتماعية للفرد',
            'relationship'            => 'صلة القرابة',
            'phone_number'            => 'الهاتف',
            'is_disabled'             => 'ذوي الاحتياجات',
        ];
    }

    public static function mapCamps(array $headers): array
    {
        return self::map($headers, self::campFieldLabels(), self::campKeywords());
    }

    public static function mapMembers(array $headers): array
    {
        return self::map($headers, self::memberFieldLabels(), self::memberKeywords());
    }

    private static function campKeywords(): array
    {
        return [
            'name'              => ['camp name', 'اسم المخيم', 'المخيم', 'name', 'camp', 'اسم'],
            'location'          => ['location', 'الموقع', 'address', 'عنوان', 'مكان'],
            'latitude'          => ['latitude', 'lat', 'خط العرض', 'عرض'],
            'longitude'         => ['longitude', 'lng', 'lon', 'خط الطول', 'طول'],
            'capacity'          => ['capacity', 'الطاقة', 'سعة', 'استيعاب', 'الطاقة الاستيعابية'],
            'current_occupancy' => ['occupancy', 'الإشغال', 'occupied', 'current'],
            'manager'           => ['manager', 'مدير', 'المسؤول', 'مسؤول'],
            'phone'             => ['phone', 'tel', 'هاتف', 'جوال', 'mobile'],
            'description'       => ['description', 'وصف', 'notes', 'ملاحظات'],
            'status'            => ['status', 'الحالة'],
            'is_active'         => ['active', 'نشط', 'is_active', 'enabled'],
        ];
    }

    private static function memberKeywords(): array
    {
        return [
            'guardian_card_id'        => ['guardian card', 'guardian id', 'هوية رب الأسرة', 'هوية ولي الأمر', 'رقم هوية رب الأسرة', 'رب الأسرة', 'ولي الأمر', 'parent id', 'head id', 'رقم الهوية'],
            'guardian_name'           => ['guardian name', 'اسم رب الأسرة', 'اسم ولي الأمر', 'ولي الأمر', 'parent name', 'اسم رب العائلة', 'head of household'],
            'guardian_marital_status' => ['guardian marital', 'marital status guardian', 'حالة رب الأسرة', 'marital', 'حالة اجتماعية', 'social status', 'marital status'],
            'guardian_camp'           => ['camp', 'مخيم', 'اسم المخيم', 'المخيم', 'camp name'],
            'name'                    => ['member name', 'full name', 'الاسم', 'اسم الفرد', 'الاسم الكامل', 'fullname', 'full_name', 'name'],
            'card_id'                 => ['member card', 'member id', 'card id', 'رقم البطاقة', 'رقم الهوية', 'national id', 'id number', 'هوية'],
            'gender'                  => ['gender', 'جنس', 'sex', 'ذكر', 'أنثى'],
            'date_of_birth'           => ['birth', 'dob', 'الميلاد', 'تاريخ الميلاد', 'date of birth'],
            'nationality'             => ['nationality', 'جنسية', 'country'],
            'marital_status'          => ['member marital', 'marital status', 'حالة اجتماعية', 'الحالة الاجتماعية', 'social status', 'أعزب', 'متزوج', 'مطلق', 'أرمل', 'single', 'married', 'divorced', 'widowed', 'غير متزوج'],
            'relationship'            => ['relationship', 'صلة', 'قرابة', 'relation', 'kinship'],
            'phone_number'            => ['phone', 'هاتف', 'موبايل', 'mobile', 'tel', 'جوال'],
            'is_disabled'             => ['disabled', 'احتياجات', 'disability', 'اعاقة', 'ذوي'],
        ];
    }

    private static function map(array $headers, array $dbFields, array $keywords): array
    {
        $candidates = [];

        foreach ($dbFields as $field => $label) {
            foreach ($headers as $header) {
                $score = self::scoreHeader($field, (string) $header, $keywords);

                if ($score >= 5) {
                    $candidates[] = [
                        'field'  => $field,
                        'header' => (string) $header,
                        'score'  => $score,
                    ];
                }
            }
        }

        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $mapping = [];
        $usedHeaders = [];

        foreach ($candidates as $candidate) {
            if (isset($mapping[$candidate['field']]) || in_array($candidate['header'], $usedHeaders, true)) {
                continue;
            }

            $mapping[$candidate['field']] = $candidate['header'];
            $usedHeaders[] = $candidate['header'];
        }

        return $mapping;
    }

    private static function scoreHeader(string $field, string $header, array $keywords): int
    {
        $headerLower = mb_strtolower($header, 'UTF-8');
        $score = 0;
        $isGuardianField = str_starts_with($field, 'guardian_');

        foreach ($keywords[$field] ?? [] as $keyword) {
            $keywordLower = mb_strtolower($keyword, 'UTF-8');

            if ($headerLower === $keywordLower) {
                $score += 10;
            } elseif (str_contains($headerLower, $keywordLower)) {
                $score += 5;
            } elseif (str_contains($keywordLower, $headerLower)) {
                $score += 3;
            }
        }

        if ($isGuardianField && $score > 0) {
            $hasGuardianMarker = str_contains($headerLower, 'guardian')
                || str_contains($headerLower, 'رب الأسرة')
                || str_contains($headerLower, 'رب اسرة')
                || str_contains($headerLower, 'ولي الأمر')
                || str_contains($headerLower, 'ولي الامر')
                || str_contains($headerLower, 'parent')
                || str_contains($headerLower, 'head');

            if ($hasGuardianMarker) {
                $score += 8;
            }
        }

        if ($field === 'card_id' && $score > 0) {
            $hasMemberMarker = str_contains($headerLower, 'member')
                || str_contains($headerLower, 'فرد')
                || str_contains($headerLower, 'individual');

            if ($hasMemberMarker) {
                $score += 4;
            }

            if (str_contains($headerLower, 'guardian') || str_contains($headerLower, 'رب')) {
                $score -= 6;
            }
        }

        if ($field === 'marital_status' && $score > 0) {
            if (str_contains($headerLower, 'guardian')
                || str_contains($headerLower, 'رب')
                || str_contains($headerLower, 'ولي')
                || str_contains($headerLower, 'parent')) {
                $score -= 8;
            }

            if (str_contains($headerLower, 'member') || str_contains($headerLower, 'فرد')) {
                $score += 4;
            }
        }

        return $score;
    }
}
