<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'camp_id',
        'first_name',
        'second_name',
        'third_name',
        'family_name',
        'date_of_birth',
        'gender',
        'card_id',
        'phone',
        'marital_status',
        'nationality',
        'family_member_number',
        'is_disabled',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'family_member_number' => 'integer',
        'is_disabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Get aid allocations for this guardian/family
     */
    public function aidAllocations(): HasMany
    {
        return $this->hasMany(FamilyAidAllocation::class);
    }

    /**
     * Get pending aid allocations
     */
    public function pendingAidAllocations(): HasMany
    {
        return $this->aidAllocations()->where('receipt_status', 'pending');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->second_name} {$this->third_name} {$this->family_name}");
    }

    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    public function getTotalFamilySizeAttribute(): int
    {
        return 1 + $this->familyMembers()->count(); // Guardian + family members
    }

    public function updateFamilyMemberCount(): void
    {
        $this->family_member_number = $this->familyMembers()->count();
        $this->save();
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByMaritalStatus($query, string $status)
    {
        return $query->where('marital_status', $status);
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
   
}