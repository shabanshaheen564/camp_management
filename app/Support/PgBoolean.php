<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class PgBoolean
{
    public static function where(Builder $query, string $column, bool $value): Builder
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->whereRaw($value ? "{$column} IS TRUE" : "{$column} IS FALSE");
        }

        return $query->where($column, $value);
    }
}
