<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guardian_id',
        'name',
        'gender',
        'card_id',
        'date_of_birth',
        'nationality',
        'phone_number',
        'is_disabled',
        'marital_status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_disabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class, 'camp_id', 'id')->through('guardian');
    }

    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    public function getIsChildAttribute(): bool
    {
        return $this->age < 18;
    }

    public function getIsElderlyAttribute(): bool
    {
        return $this->age >= 60;
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeChildren($query)
    {
        return $query->whereDate('date_of_birth', '>', now()->subYears(18));
    }

    public function scopeAdults($query)
    {
        return $query->whereDate('date_of_birth', '<=', now()->subYears(18));
    }

    public function scopeElderly($query)
    {
        return $query->whereDate('date_of_birth', '<=', now()->subYears(60));
    }

    public function scopeDisabled($query, bool $disabled = true)
    {
        return $query->where('is_disabled', $disabled);
    }

    public function scopeByAge($query, int $minAge = null, int $maxAge = null)
    {
        if ($minAge) {
            $query->whereDate('date_of_birth', '<=', now()->subYears($minAge));
        }
        
        if ($maxAge) {
            $query->whereDate('date_of_birth', '>=', now()->subYears($maxAge + 1));
        }
        
        return $query;
    }

    protected static function booted()
    {
        static::created(function ($familyMember) {
            $familyMember->guardian->updateFamilyMemberCount();
            $familyMember->guardian->camp->updateOccupancy();
        });

        static::updated(function ($familyMember) {
            $familyMember->guardian->updateFamilyMemberCount();
            $familyMember->guardian->camp->updateOccupancy();
        });

        static::deleted(function ($familyMember) {
            $familyMember->guardian->updateFamilyMemberCount();
            $familyMember->guardian->camp->updateOccupancy();
        });
    }
}