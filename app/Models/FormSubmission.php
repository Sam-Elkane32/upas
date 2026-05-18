<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'user_id',
        'campus_code',
        'strategic_goal_id',
        'kra_id',
        'kpi_id',
        'quarter',
        'target_value',
        'actual_value',
        'variance',
        'rate_of_accomplishment',
        'descriptive_rating',
        'remarks',
        'attachment_path',
        'status', // 'draft', 'pending', 'approved', 'returned'
        'reviewer_id',
        'reviewed_at',
        'return_comments',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'variance' => 'decimal:2',
        'rate_of_accomplishment' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code');
    }

    public function strategicGoal(): BelongsTo
    {
        return $this->belongsTo(StrategicGoal::class);
    }

    public function kra(): BelongsTo
    {
        return $this->belongsTo(KRA::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(KPI::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Auto-generate submission ID
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->submission_id)) {
                $model->submission_id = 'UPAS-' . strtoupper(uniqid()) . '-' . now()->format('Ymd');
            }
        });
    }

    // Auto-calculate variance and rate
    public function calculateMetrics()
    {
        $this->variance = (float) ($this->actual_value - $this->target_value);
        $this->rate_of_accomplishment = $this->target_value > 0 
            ? (float) (($this->actual_value / $this->target_value) * 100)
            : 0.0;
        
        // Set descriptive rating based on rate
        $this->descriptive_rating = $this->getDescriptiveRating();
    }

    private function getDescriptiveRating(): string
    {
        $rate = $this->rate_of_accomplishment;
        
        if ($rate >= 100) return 'Outstanding';
        if ($rate >= 90) return 'Very Satisfactory';
        if ($rate >= 80) return 'Satisfactory';
        if ($rate >= 70) return 'Fair';
        return 'Needs Improvement';
    }

    // Accessor for status badge
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Unpublished',
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'returned' => 'Returned',
            default => 'Unknown'
        };
    }

    // Accessor for status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'returned' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Check if submission is editable
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'returned']);
    }

    // Scopes
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCampus($query, $campusCode)
    {
        return $query->where('campus_code', $campusCode);
    }

    public function scopeForQuarter($query, $quarter)
    {
        return $query->where('quarter', $quarter);
    }
}