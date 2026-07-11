<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AidType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_en',
        'description',
        'unit',
        'category',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all aid distributions for this aid type
     */
    public function aidDistributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }

    /**
     * Get active aid distributions for this aid type
     */
    public function activeDistributions(): HasMany
    {
        return $this->aidDistributions()->where('status', 'active');
    }

    /**
     * Scope for active aid types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get total available quantity across all active distributions
     */
    public function getTotalAvailableAttribute()
    {
        return $this->activeDistributions()->sum('available_quantity');
    }

    /**
     * Get total distributed quantity across all distributions
     */
    public function getTotalDistributedAttribute()
    {
        return $this->aidDistributions()->sum('distributed_quantity');
    }

    /**
     * Get remaining stock
     */
    public function getRemainingStockAttribute()
    {
        return $this->total_available - $this->total_distributed;
    }

    /**
     * Categories constants
     */
    const CATEGORY_FOOD = 'food';
    const CATEGORY_WATER = 'water';
    const CATEGORY_MEDICAL = 'medical';
    const CATEGORY_CLOTHING = 'clothing';
    const CATEGORY_SHELTER = 'shelter';
    const CATEGORY_HYGIENE = 'hygiene';
    const CATEGORY_BASIC = 'basic';

    /**
     * Get all categories
     */
    public static function getCategories()
    {
        return [
            self::CATEGORY_FOOD => 'غذاء',
            self::CATEGORY_WATER => 'مياه',
            self::CATEGORY_MEDICAL => 'طبي',
            self::CATEGORY_CLOTHING => 'ملابس',
            self::CATEGORY_SHELTER => 'مأوى',
            self::CATEGORY_HYGIENE => 'نظافة',
            self::CATEGORY_BASIC => 'أساسي',
        ];
    }
}