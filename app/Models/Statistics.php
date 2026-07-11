<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Statistics extends Model
{
    protected $fillable = [
        'camp_id',
        'family_numbers',
        'family_member_avg',
        'disabled_people_number',
        'female_number',
        'male_number',
        'married_number',
        'single_number',
        'old_people_number',
        'capacity_ratio',
    ];

    protected $casts = [
        'family_numbers' => 'integer',
        'family_member_avg' => 'decimal:2',
        'disabled_people_number' => 'integer',
        'female_number' => 'integer',
        'male_number' => 'integer',
        'married_number' => 'integer',
        'single_number' => 'integer',
        'old_people_number' => 'integer',
        'capacity_ratio' => 'decimal:2',
    ];

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

    public static function generateForCamp(Camp $camp): self
    {
        $guardians = $camp->guardians()->with('familyMembers')->get();
        
        // Calculate family statistics
        $familyNumbers = $guardians->count();
        $totalFamilyMembers = $guardians->sum(function ($guardian) {
            return $guardian->familyMembers->count();
        });
        $familyMemberAvg = $familyNumbers > 0 ? ($totalFamilyMembers + $familyNumbers) / $familyNumbers : 0;
        
        // Gender statistics (guardians + family members)
        $maleNumber = $guardians->where('gender', 'male')->count() +
                      $guardians->flatMap->familyMembers->where('gender', 'male')->count();
        $femaleNumber = $guardians->where('gender', 'female')->count() +
                        $guardians->flatMap->familyMembers->where('gender', 'female')->count();
        
        // Disability statistics
        $disabledPeopleNumber = $guardians->where('is_disabled', true)->count() +
                                $guardians->flatMap->familyMembers->where('is_disabled', true)->count();
        
        // Marital status (guardians only)
        $marriedNumber = $guardians->where('marital_status', 'married')->count();
        $singleNumber = $guardians->whereIn('marital_status', ['single', 'divorced', 'widowed'])->count();
        
        // Age statistics (60+ years old)
        $oldPeopleNumber = $guardians->where('age', '>=', 60)->count() +
                          $guardians->flatMap->familyMembers->where('age', '>=', 60)->count();
        
        // Capacity ratio
        $capacityRatio = $camp->capacity > 0 ? ($camp->current_occupancy / $camp->capacity) * 100 : 0;
        
        return self::updateOrCreate(
            ['camp_id' => $camp->id],
            [
                'family_numbers' => $familyNumbers,
                'family_member_avg' => $familyMemberAvg,
                'disabled_people_number' => $disabledPeopleNumber,
                'female_number' => $femaleNumber,
                'male_number' => $maleNumber,
                'married_number' => $marriedNumber,
                'single_number' => $singleNumber,
                'old_people_number' => $oldPeopleNumber,
                'capacity_ratio' => $capacityRatio,
            ]
        );
    }

    public function getTotalPeopleAttribute(): int
    {
        return $this->male_number + $this->female_number;
    }

    public function getChildrenNumberAttribute(): int
    {
        return max(0, $this->total_people - $this->old_people_number);
    }

    public function getGenderRatioAttribute(): array
    {
        $total = $this->total_people;
        
        return [
            'male_percentage' => $total > 0 ? round(($this->male_number / $total) * 100, 1) : 0,
            'female_percentage' => $total > 0 ? round(($this->female_number / $total) * 100, 1) : 0,
        ];
    }
}