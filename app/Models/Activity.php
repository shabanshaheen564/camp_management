<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'icon',
        'color',
        'user_id',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject of the activity (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get formatted time for display
     */
    public function getFormattedTimeAttribute(): string
    {
        $diffInMinutes = round($this->created_at->diffInMinutes(now()));
        
        if ($diffInMinutes < 1) {
            return 'الآن';
        } else {
            return "منذ {$diffInMinutes} دقيقة";
        }
    }

    /**
     * Static method to log an activity
     */
    public static function log(
        string $type,
        string $title,
        string $description,
        $subject = null,
        array $properties = [],
        string $icon = 'info-circle',
        string $color = 'primary'
    ): self {
        return self::create([
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'user_id' => auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'properties' => $properties,
        ]);
    }

    /**
     * Get recent activities with access control
     */
    public static function getRecentForUser($user, int $limit = 10)
    {
        $query = self::with(['user', 'subject'])
            ->orderByDesc('created_at')
            ->limit($limit);

        // If user is supervisor, only show activities for their camp
        if ($user->isSupervisor() && $user->camp_id) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($subQuery) use ($user) {
                      $subQuery->where('subject_type', Camp::class)
                               ->where('subject_id', $user->camp_id);
                  })
                  ->orWhere(function($subQuery) use ($user) {
                      $subQuery->where('subject_type', Guardian::class)
                               ->whereExists(function($existsQuery) use ($user) {
                                   $existsQuery->select(\DB::raw(1))
                                              ->from('guardians')
                                              ->whereRaw('guardians.id = activities.subject_id')
                                              ->where('guardians.camp_id', $user->camp_id)
                                              ->whereNull('guardians.deleted_at');
                               });
                  });
            });
        }

        return $query->get();
    }

    /**
     * Activity type constants
     */
    const TYPE_CAMP_CREATED = 'camp_created';
    const TYPE_CAMP_UPDATED = 'camp_updated';
    const TYPE_CAMP_STATUS_CHANGED = 'camp_status_changed';
    const TYPE_GUARDIAN_REGISTERED = 'guardian_registered';
    const TYPE_GUARDIAN_UPDATED = 'guardian_updated';
    const TYPE_FAMILY_MEMBER_ADDED = 'family_member_added';
    const TYPE_FAMILY_MEMBER_UPDATED = 'family_member_updated';
    const TYPE_USER_CREATED = 'user_created';
    const TYPE_USER_UPDATED = 'user_updated';
    const TYPE_ROLE_ASSIGNED = 'role_assigned';
    const TYPE_STATISTICS_EXPORTED = 'statistics_exported';
}