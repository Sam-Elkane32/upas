<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'template_id',
        'form_id',
        'template_code',
        'form_title',
        'sg_code',
        'kra_title',
        'kpi_title',
        'campus',
        'campus_code',
        'quarter',
        'table_data',
        'status',
        'submitted_by',
        'submitted_at',
        'last_updated',
        'is_draft',
        'draft_version',
        'last_draft_at',
    ];

    protected $casts = [
        'table_data' => 'array',
        'submitted_at' => 'datetime',
        'last_updated' => 'datetime',
        'last_draft_at' => 'datetime',
        'is_draft' => 'boolean',
        'draft_version' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id', 'id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * One approval row per submission (approvals.submission_id → submissions.id).
     */
    public function approval(): HasOne
    {
        return $this->hasOne(Approval::class, 'submission_id', 'id');
    }

    /**
     * Auto-generate submission ID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->submission_id)) {
                $model->submission_id = 'UPAS-' . strtoupper(uniqid()) . '-' . now()->format('Ymd');
            }
            // Only set submitted_at if it's not a draft
            if (!$model->is_draft && empty($model->submitted_at)) {
                $model->submitted_at = now();
            }
            $model->last_updated = now();
        });

        static::updating(function ($model) {
            $model->last_updated = now();
        });
    }

    /**
     * Check if submission is editable
     * Only Unpublished and Returned submissions can be edited
     * Once submitted (Pending Review or Approved), it cannot be edited unless QA Coordinator returns it
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['Returned', 'Unpublished']);
    }

    /**
     * Check if submission is a draft
     */
    public function isDraft(): bool
    {
        return $this->is_draft || $this->status === 'Unpublished';
    }

    /**
     * Scope for draft submissions
     * Wrapped in closure so it chains correctly with other scopes (e.g. forUser)
     */
    public function scopeDrafts($query)
    {
        return $query->where(function ($q) {
            $q->where('is_draft', true)->orWhere('status', 'Unpublished');
        });
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'Pending Review' => 'bg-yellow-100 text-yellow-800',
            'Approved' => 'bg-green-100 text-green-800',
            'Returned' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('submitted_by', $userId);
    }

    public function scopeForCampus($query, $campus)
    {
        return $query->where('campus', $campus);
    }

    /**
     * Scope for campus by name or code (for QA/approval flow alignment)
     */
    public function scopeForCampusNameOrCode($query, string $campusName, ?string $campusCode = null)
    {
        if ($campusCode) {
            return $query->where(function ($q) use ($campusName, $campusCode) {
                $q->where('campus', $campusName)->orWhere('campus_code', $campusCode);
            });
        }
        return $query->where('campus', $campusName);
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'Pending Review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'Returned');
    }
}
