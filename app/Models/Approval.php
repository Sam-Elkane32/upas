<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_id',
        'submission_id',
        'accomp_term',
        'sdp_ref',
        'target_q1',
        'target_q2',
        'target_q3',
        'target_q4',
        'target_total',
        'accomp_q1',
        'accomp_q2',
        'accomp_q3',
        'accomp_q4',
        'accomp_total',
        'variance',
        'rate',
        'rating',
        'remarks',
        'verified_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'target_q1' => 'decimal:2',
        'target_q2' => 'decimal:2',
        'target_q3' => 'decimal:2',
        'target_q4' => 'decimal:2',
        'target_total' => 'decimal:2',
        'accomp_q1' => 'decimal:2',
        'accomp_q2' => 'decimal:2',
        'accomp_q3' => 'decimal:2',
        'accomp_q4' => 'decimal:2',
        'accomp_total' => 'decimal:2',
        'variance' => 'decimal:2',
        'rate' => 'decimal:2',
        'validated_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Auto-generate approval ID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->approval_id)) {
                $model->approval_id = 'APP-' . strtoupper(uniqid()) . '-' . now()->format('Ymd');
            }
        });

        static::saving(function ($model) {
            // Auto-calculate totals
            $model->target_total = $model->target_q1 + $model->target_q2 + $model->target_q3 + $model->target_q4;
            $model->accomp_total = $model->accomp_q1 + $model->accomp_q2 + $model->accomp_q3 + $model->accomp_q4;
            
            // Auto-calculate variance (target first: shortfall is positive when below target)
            $model->variance = (float) ($model->target_total - $model->accomp_total);
            
            // Auto-calculate rate
            $model->rate = $model->target_total > 0 
                ? (float) (($model->accomp_total / $model->target_total) * 100)
                : 0.0;
            
            // Auto-calculate rating based on rate
            $model->rating = $model->getDescriptiveRating((float) $model->rate);
        });
    }

    /**
     * Get descriptive rating based on rate
     */
    public function getDescriptiveRating(float $rate): string
    {
        if ($rate >= 100) return 'Outstanding';
        if ($rate >= 90) return 'Very Satisfactory';
        if ($rate >= 80) return 'Satisfactory';
        if ($rate >= 70) return 'Fair';
        return 'Needs Improvement';
    }

    /**
     * Calculate performance metrics
     */
    public function calculateMetrics(): array
    {
        $this->target_total = $this->target_q1 + $this->target_q2 + $this->target_q3 + $this->target_q4;
        $this->accomp_total = $this->accomp_q1 + $this->accomp_q2 + $this->accomp_q3 + $this->accomp_q4;
        $this->variance = $this->target_total - $this->accomp_total;
        $this->rate = $this->target_total > 0 
            ? ($this->accomp_total / $this->target_total) * 100 
            : 0;
        $this->rating = $this->getDescriptiveRating($this->rate);

        return [
            'target_total' => $this->target_total,
            'accomp_total' => $this->accomp_total,
            'variance' => $this->variance,
            'rate' => $this->rate,
            'rating' => $this->rating,
        ];
    }

    /**
     * Get rating badge class
     */
    public function getRatingBadgeClassAttribute(): string
    {
        return match($this->rating) {
            'Outstanding' => 'bg-green-100 text-green-800',
            'Very Satisfactory' => 'bg-blue-100 text-blue-800',
            'Satisfactory' => 'bg-yellow-100 text-yellow-800',
            'Fair' => 'bg-orange-100 text-orange-800',
            'Needs Improvement' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Check if approval is completed
     */
    public function isCompleted(): bool
    {
        return !is_null($this->validated_at);
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('validated_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('validated_at');
    }

    public function scopeForValidator($query, $validatorId)
    {
        return $query->where('validated_by', $validatorId);
    }

    public function scopeForCampus($query, $campus)
    {
        return $query->whereHas('submission', function($q) use ($campus) {
            $q->where('campus', $campus);
        });
    }
}