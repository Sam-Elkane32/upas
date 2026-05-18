<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccomplishmentPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'objective',
        'what_will_be_done',
        'resources_needed',
        'responsible_person',
        'target_date',
        'completion_date',
        'evaluation_method',
        'status',
        'priority',
        'category',
        'progress_percentage',
        'notes',
        'is_active',
        'aop_id', // Link to Annual Operational Plan
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'completion_date' => 'datetime',
        'progress_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ON_HOLD = 'on_hold';

    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Category constants for different types of accomplishments
     */
    public const CATEGORY_TEACHING = 'teaching';
    public const CATEGORY_RESEARCH = 'research';
    public const CATEGORY_EXTENSION = 'extension';
    public const CATEGORY_ADMINISTRATIVE = 'administrative';
    public const CATEGORY_PROFESSIONAL_DEVELOPMENT = 'professional_development';

    /**
     * Get the user that owns the accomplishment plan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all activities for this accomplishment plan
     */
    public function activities(): HasMany
    {
        return $this->hasMany(AccomplishmentActivity::class);
    }

    /**
     * Check if the accomplishment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->target_date < now() && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Check if the accomplishment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get the completion percentage
     */
    public function getCompletionPercentage(): int
    {
        return $this->progress_percentage ?? 0;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress_percentage' => 100,
            'completion_date' => now(),
        ]);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_ON_HOLD => 'secondary',
            self::STATUS_CANCELLED => 'danger',
            default => 'light',
        };
    }
}
