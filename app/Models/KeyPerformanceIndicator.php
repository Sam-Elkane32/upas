<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyPerformanceIndicator extends Model
{
    protected $fillable = [
        'strategic_goal_id',
        'name',
        'description',
        'measurement_type',
        'target_value',
        'current_value',
        'unit_of_measure',
        'frequency',
        'deadline',
        'status',
        'calculation_method',
        'quarterly_targets'
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'deadline' => 'date',
        'quarterly_targets' => 'array'
    ];

    /**
     * Get the strategic goal that owns this KPI
     */
    public function strategicGoal(): BelongsTo
    {
        return $this->belongsTo(StrategicGoal::class);
    }

    /**
     * Calculate achievement percentage
     */
    public function getAchievementPercentageAttribute(): float
    {
        if ($this->target_value == 0) {
            return 0;
        }

        $percentage = ($this->current_value / $this->target_value) * 100;
        return round(min($percentage, 100), 2);
    }

    /**
     * Check if KPI is overdue
     */
    public function isOverdue(): bool
    {
        return now()->gt($this->deadline) && $this->status !== 'Achieved';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Not Started' => 'bg-gray-100 text-gray-800',
            'In Progress' => 'bg-blue-100 text-blue-800',
            'Achieved' => 'bg-green-100 text-green-800',
            'Overdue' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Update status based on achievement and deadline
     */
    public function updateStatus(): void
    {
        if ($this->achievement_percentage >= 100) {
            $this->status = 'Achieved';
        } elseif ($this->isOverdue()) {
            $this->status = 'Overdue';
        } elseif ($this->current_value > 0) {
            $this->status = 'In Progress';
        } else {
            $this->status = 'Not Started';
        }
        $this->save();
    }

    /**
     * Get formatted value with unit
     */
    public function getFormattedCurrentValueAttribute(): string
    {
        $value = number_format($this->current_value, 2);
        return $this->unit_of_measure ? $value . ' ' . $this->unit_of_measure : $value;
    }

    /**
     * Get formatted target with unit
     */
    public function getFormattedTargetValueAttribute(): string
    {
        $value = number_format($this->target_value, 2);
        return $this->unit_of_measure ? $value . ' ' . $this->unit_of_measure : $value;
    }
}
