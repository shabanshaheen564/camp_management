<?php

namespace App\Models;

use App\Support\PgBoolean;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'capacity',
        'current_occupancy',
        'manager',
        'phone',
        'description',
        'status',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class, 'camp_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function aidDistributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }

    public function activeAidDistributions(): HasMany
    {
        return $this->aidDistributions()->where('status', 'active');
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(Statistics::class);
    }

    public function getOccupancyRateAttribute(): float
    {
        return $this->capacity > 0 ? ($this->current_occupancy / $this->capacity) * 100 : 0;
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->current_occupancy);
    }

    /**
     * Recalculate occupancy from the actual family/member records and keep
     * the human-facing status consistent with is_active and capacity.
     */
    public function updateOccupancy(): void
    {
        $totalGuardians = $this->guardians()->count();
        $totalFamilyMembers = $this->guardians()
            ->withCount('familyMembers')
            ->get()
            ->sum('family_members_count');

        $this->current_occupancy = $totalGuardians + $totalFamilyMembers;

        if (!$this->is_active) {
            $this->status = 'inactive';
        } elseif ($this->current_occupancy >= $this->capacity) {
            $this->status = 'full';
        } else {
            $this->status = 'active';
        }

        $this->save();
    }

    public function scopeActive($query)
    {
        return PgBoolean::where($query, $this->getTable().'.is_active', true);
    }
}
