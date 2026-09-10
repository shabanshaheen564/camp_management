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
            'guardian_card_id'        => 'رقم هوية رب الأسرة',
            'guardian_name'           => 'اسم رب الأسرة',
            'guardian_marital_status' => 'الحالة الاجتماعية لرب الأسرة',
            'guardian_camp'           => 'اسم المخيم',
            'name'                    => 'اسم الفرد',
            'card_id'                 => 'رقم هوية الفرد',
            'gender'                  => 'الجنس',
            'date_of_birth'           => 'تاريخ الميلاد',
            'nationality'             => 'الجنسية',
            'marital_status'          => 'الحالة الاجتماعية للفرد',
            'relationship'            => 'صلة القرابة',
            'phone_number'            => 'الهاتف',
            'is_disabled'             => 'ذوو الاحتياجات',
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
            'guardian_card_id'        => [
                'guardian card id', 'guardian card', 'guardian id',
                'رقم هوية رب الأسرة', 'رقم هوية رب الاسرة', 'هوية رب الأسرة', 'هوية رب الاسرة',
                'هوية ولي الأمر', 'هوية ولي الامر', 'رقم هوية ولي الأمر', 'رقم هوية ولي الامر',
                'parent id', 'parent card id', 'head id', 'head of household id'
            ],
            'guardian_name'           => [
                'guardian name', 'guardian', 'اسم رب الأسرة', 'اسم رب الاسرة',
                'اسم ولي الأمر', 'اسم ولي الامر', 'parent name', 'اسم رب العائلة', 'head of household name'
            ],
            'guardian_marital_status' => [
                'guardian marital', 'marital status guardian', 'حالة رب الأسرة', 'حالة رب الاسرة',
                'حالة ولي الأمر', 'حالة ولي الامر', 'guardian status', 'social status guardian'
            ],
            'guardian_camp'           => ['camp', 'مخيم', 'اسم المخيم', 'المخيم', 'camp name'],
            'name'                    => ['member name', 'full name', 'الاسم', 'اسم الفرد', 'الاسم الكامل', 'fullname', 'full_name', 'member', 'name'],
            'card_id'                 => ['member card id', 'member card', 'member id', 'رقم هوية الفرد', 'هوية الفرد', 'رقم بطاقة الفرد', 'رقم البطاقة للفرد', 'card id', 'national id', 'id number', 'individual id', 'individual card id'],
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
        $headerLower = mb_strtolower(trim($header), 'UTF-8');
        $score = 0;

        foreach ($keywords[$field] ?? [] as $keyword) {
            $keywordLower = mb_strtolower(trim($keyword), 'UTF-8');

            if ($headerLower === $keywordLower) {
                $score += 10;
            } elseif (str_contains($headerLower, $keywordLower)) {
                $score += 5;
            } elseif (str_contains($keywordLower, $headerLower)) {
                $score += 3;
            }
        }

        // "Guardian" alone means the guardian's name, not the guardian's ID.
        if ($field === 'guardian_name' && $headerLower === 'guardian') {
            $score += 15;
        }

        // Guardian Card ID must contain an explicit ID/card/identity marker.
        // A generic "Guardian" column must never be used as the guardian ID.
        if ($field === 'guardian_card_id') {
            $hasIdMarker = str_contains($headerLower, 'card')
                || str_contains($headerLower, 'id')
                || str_contains($headerLower, 'هوية')
                || str_contains($headerLower, 'بطاقة');

            $hasGuardianMarker = str_contains($headerLower, 'guardian')
                || str_contains($headerLower, 'رب الأسرة')
                || str_contains($headerLower, 'رب الاسرة')
                || str_contains($headerLower, 'ولي الأمر')
                || str_contains($headerLower, 'ولي الامر')
                || str_contains($headerLower, 'parent')
                || str_contains($headerLower, 'head');

            if (!$hasIdMarker || !$hasGuardianMarker) {
                $score = 0;
            } else {
                $score += 12;
            }
        }

        if ($field === 'guardian_name' && $score > 0) {
            $hasIdMarker = str_contains($headerLower, 'card id')
                || str_contains($headerLower, 'guardian id')
                || str_contains($headerLower, 'national id')
                || str_contains($headerLower, 'هوية')
                || str_contains($headerLower, 'بطاقة');

            if ($hasIdMarker) {
                $score = 0;
            }
        }

        if ($field === 'card_id' && $score > 0) {
            $hasMemberMarker = str_contains($headerLower, 'member')
                || str_contains($headerLower, 'فرد')
                || str_contains($headerLower, 'individual');

            if ($hasMemberMarker) {
                $score += 8;
            }

            if (str_contains($headerLower, 'guardian')
                || str_contains($headerLower, 'رب الأسرة')
                || str_contains($headerLower, 'رب الاسرة')
                || str_contains($headerLower, 'ولي الأمر')
                || str_contains($headerLower, 'ولي الامر')
                || str_contains($headerLower, 'parent')
                || str_contains($headerLower, 'head')) {
                $score -= 10;
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
