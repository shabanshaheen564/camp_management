<?php

namespace App\Models;

use App\Support\PgBoolean;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class FamilyMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guardian_id',
        'name',
        'relationship',
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
        return PgBoolean::where($query, $this->getTable().'.is_disabled', $disabled);
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
        // Capacity must also be enforced for API/import paths that create
        // FamilyMember directly and therefore bypass FamilyController.
        static::creating(function ($familyMember) {
            $guardian = $familyMember->guardian()->with('camp')->first();

            if (!$guardian || !$guardian->camp) {
                return;
            }

            $camp = $guardian->camp;
            $currentPeople = $camp->guardians()->count()
                + $camp->guardians()->withCount('familyMembers')->get()->sum('family_members_count');

            if ($currentPeople >= $camp->capacity) {
                throw ValidationException::withMessages([
                    'guardian_id' => "لا يمكن إضافة الفرد. المخيم ({$camp->name}) وصل إلى سعته القصوى ({$camp->capacity} شخص).",
                ]);
            }
        });

        static::created(function ($familyMember) {
            if (!$familyMember->relationLoaded('guardian')) {
                $familyMember->load('guardian.camp');
            }
            $guardian = $familyMember->guardian;
            if ($guardian && $guardian->camp) {
                $guardian->updateFamilyMemberCount();
                $guardian->camp->updateOccupancy();
            }
        });

        static::updated(function ($familyMember) {
            if (!$familyMember->relationLoaded('guardian')) {
                $familyMember->load('guardian.camp');
            }
            $guardian = $familyMember->guardian;
            if ($guardian && $guardian->camp) {
                $guardian->updateFamilyMemberCount();
                $guardian->camp->updateOccupancy();
            }
        });

        static::deleted(function ($familyMember) {
            if (!$familyMember->relationLoaded('guardian')) {
                $familyMember->load('guardian.camp');
            }
            $guardian = $familyMember->guardian;
            if ($guardian && $guardian->camp) {
                $guardian->updateFamilyMemberCount();
                $guardian->camp->updateOccupancy();
            }
        });
    }
}
