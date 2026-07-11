<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AidDistribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'camp_id',
        'aid_type_id',
        'available_quantity',
        'distributed_quantity',
        'time_period',
        'target_beneficiaries',
        'distribution_basis',
        'distribution_date',
        'expiry_date',
        'status',
        'priority',
        'special_notes',
        'created_by',
        'managed_by',
    ];

    protected $casts = [
        'available_quantity' => 'decimal:2',
        'distributed_quantity' => 'decimal:2',
        'target_beneficiaries' => 'integer',
        'distribution_date' => 'date',
        'expiry_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the camp that owns this distribution
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    /**
     * Get the aid type
     */
    public function aidType(): BelongsTo
    {
        return $this->belongsTo(AidType::class);
    }

    /**
     * Get the user who created this distribution
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user managing this distribution
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    /**
     * Get all family allocations for this distribution
     */
    public function familyAllocations(): HasMany
    {
        return $this->hasMany(FamilyAidAllocation::class);
    }

    /**
     * Get pending allocations
     */
    public function pendingAllocations(): HasMany
    {
        return $this->familyAllocations()->where('receipt_status', 'pending');
    }

    /**
     * Get distributed allocations
     */
    public function distributedAllocations(): HasMany
    {
        return $this->familyAllocations()->where('receipt_status', 'distributed');
    }

    /**
     * Get remaining quantity
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->available_quantity - $this->distributed_quantity;
    }

    /**
     * Get distribution percentage
     */
    public function getDistributionPercentageAttribute()
    {
        return $this->available_quantity > 0 
            ? round(($this->distributed_quantity / $this->available_quantity) * 100, 1)
            : 0;
    }

    /**
     * Calculate individual share
     */
    public function calculateIndividualShare()
    {
        if ($this->distribution_basis === 'individual' && $this->target_beneficiaries > 0) {
            return $this->available_quantity / $this->target_beneficiaries;
        }
        return 0;
    }

    /**
     * Calculate family share based on size
     */
    public function calculateFamilyShare($familySize)
    {
        if ($this->distribution_basis === 'individual') {
            return $this->calculateIndividualShare() * $familySize;
        } elseif ($this->distribution_basis === 'family') {
            $totalFamilies = $this->camp->guardians()->count();
            return $totalFamilies > 0 ? $this->available_quantity / $totalFamilies : 0;
        }
        return 0;
    }

    /**
     * Auto-generate allocations for all families in the camp
     */
    public function generateAllocations()
    {
        $guardians = $this->camp->guardians()->with('familyMembers')->get();
        
        foreach ($guardians as $guardian) {
            $familySize = 1 + $guardian->familyMembers->count(); // Guardian + family members
            $individualShare = $this->calculateIndividualShare();
            $familyShare = $this->calculateFamilyShare($familySize);
            
            $this->familyAllocations()->updateOrCreate(
                ['guardian_id' => $guardian->id],
                [
                    'family_size' => $familySize,
                    'individual_share' => $individualShare,
                    'family_share' => $familyShare,
                    'allocated_quantity' => $familyShare,
                    'priority_level' => $guardian->is_disabled || $guardian->familyMembers->where('is_child', true)->count() > 2 
                        ? 'vulnerable' : 'normal',
                ]
            );
        }
    }

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    /**
     * Priority constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Distribution basis constants
     */
    const BASIS_INDIVIDUAL = 'individual';
    const BASIS_FAMILY = 'family';
    const BASIS_HOUSEHOLD = 'household';

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }
}