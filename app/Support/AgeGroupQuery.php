<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AgeGroupQuery
{
    public static function ageExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql' => 'TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())',
            'pgsql' => 'EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth))',
            'sqlite' => "(strftime('%Y', 'now') - strftime('%Y', date_of_birth)) - (strftime('%m-%d', 'now') < strftime('%m-%d', date_of_birth))",
            default => 'TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())',
        };
    }

    /**
     * @return array<string, int>
     */
    public static function counts(Builder $query): array
    {
        $age = self::ageExpression();
        $base = (clone $query)->whereNotNull('date_of_birth');

        return [
            'أقل من 18' => (clone $base)->whereRaw("{$age} < 18")->count(),
            '18 - 35' => (clone $base)->whereRaw("{$age} BETWEEN 18 AND 35")->count(),
            '36 - 60' => (clone $base)->whereRaw("{$age} BETWEEN 36 AND 60")->count(),
            'أكبر من 60' => (clone $base)->whereRaw("{$age} > 60")->count(),
        ];
    }
}
