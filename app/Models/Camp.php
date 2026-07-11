<?php

namespace App\Models;

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

    /**
     * Get aid distributions for this camp
     */
    public function aidDistributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }

    /**
     * Get active aid distributions
     */
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

    public function updateOccupancy(): void
    {
        $totalGuardians = $this->guardians()->count();
        $totalFamilyMembers = $this->guardians()
            ->withCount('familyMembers')
            ->get()
            ->sum('family_members_count');
        
        $this->current_occupancy = $totalGuardians + $totalFamilyMembers;
        
        if ($this->current_occupancy >= $this->capacity) {
            $this->status = 'full';
        } elseif ($this->current_occupancy > 0) {
            $this->status = 'active';
        }
        
        $this->save();
    }

    /**
     * Scope for active camps
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}