<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyAidAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'aid_distribution_id',
        'guardian_id',
        'family_size',
        'individual_share',
        'family_share',
        'allocated_quantity',
        'receipt_status',
        'priority_level',
        'special_needs',
        'distributed_at',
        'distributed_by',
        'distribution_notes',
    ];

    protected $casts = [
        'family_size' => 'integer',
        'individual_share' => 'decimal:2',
        'family_share' => 'decimal:2',
        'allocated_quantity' => 'decimal:2',
        'distributed_at' => 'datetime',
    ];

    /**
     * Get the aid distribution
     */
    public function aidDistribution(): BelongsTo
    {
        return $this->belongsTo(AidDistribution::class);
    }

    /**
     * Get the guardian (family head)
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get the user who distributed the aid
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    /**
     * Mark as distributed
     */
    public function markAsDistributed($distributedBy = null, $notes = null)
    {
        $this->update([
            'receipt_status' => self::STATUS_DISTRIBUTED,
            'distributed_at' => now(),
            'distributed_by' => $distributedBy ?? auth()->id(),
            'distribution_notes' => $notes,
        ]);

        // Update the main distribution quantity
        $this->aidDistribution->increment('distributed_quantity', $this->allocated_quantity);

        // Log activity
        Activity::log(
            'aid_distributed',
            "تم توزيع المساعدة على عائلة {$this->guardian->full_name}",
            "تم توزيع {$this->allocated_quantity} {$this->aidDistribution->aidType->unit} من {$this->aidDistribution->aidType->name}",
            $this->guardian,
            [
                'aid_type' => $this->aidDistribution->aidType->name,
                'quantity' => $this->allocated_quantity,
                'unit' => $this->aidDistribution->aidType->unit,
                'family_size' => $this->family_size,
            ],
            'hands-helping',
            'success'
        );
    }

    /**
     * Mark as collected (family came to pick up)
     */
    public function markAsCollected($distributedBy = null, $notes = null)
    {
        $this->update([
            'receipt_status' => self::STATUS_COLLECTED,
            'distributed_at' => now(),
            'distributed_by' => $distributedBy ?? auth()->id(),
            'distribution_notes' => $notes,
        ]);

        $this->aidDistribution->increment('distributed_quantity', $this->allocated_quantity);
    }

    /**
     * Mark as missed (family didn't show up)
     */
    public function markAsMissed($notes = null)
    {
        $this->update([
            'receipt_status' => self::STATUS_MISSED,
            'distribution_notes' => $notes,
        ]);
    }

    /**
     * Get formatted distribution status
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'في الانتظار',
            'distributed' => 'تم التوزيع',
            'collected' => 'تم الاستلام',
            'missed' => 'لم يستلم',
        ];

        return $labels[$this->receipt_status] ?? $this->receipt_status;
    }

    /**
     * Get priority level in Arabic
     */
    public function getPriorityLabelAttribute()
    {
        $labels = [
            'normal' => 'عادي',
            'vulnerable' => 'أولوية',
            'urgent' => 'عاجل',
        ];

        return $labels[$this->priority_level] ?? $this->priority_level;
    }

    /**
     * Receipt status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_DISTRIBUTED = 'distributed';
    const STATUS_COLLECTED = 'collected';
    const STATUS_MISSED = 'missed';

    /**
     * Priority level constants
     */
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_VULNERABLE = 'vulnerable';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('receipt_status', self::STATUS_PENDING);
    }

    public function scopeDistributed($query)
    {
        return $query->where('receipt_status', self::STATUS_DISTRIBUTED);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority_level', $priority);
    }

    public function scopeVulnerable($query)
    {
        return $query->whereIn('priority_level', [self::PRIORITY_VULNERABLE, self::PRIORITY_URGENT]);
    }
}