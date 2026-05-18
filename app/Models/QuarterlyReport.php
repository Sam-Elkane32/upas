<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarterlyReport extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'year',
        'quarter',
        'status',
        'submission_date',
        'approval_date',
        'approved_by',
        'executive_summary',
        'achievements',
        'challenges',
        'kpi_results',
        'recommendations',
        'next_quarter_plans',
        'overall_rating',
        'reviewer_comments',
        'report_file_path'
    ];

    protected $casts = [
        'year' => 'integer',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'achievements' => 'array',
        'challenges' => 'array',
        'kpi_results' => 'array',
        'overall_rating' => 'decimal:2'
    ];

    /**
     * Get the user who created this report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department for this report
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who approved this report
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if report is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['Unpublished', 'Rejected']);
    }

    /**
     * Check if report can be submitted
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'Unpublished' && 
               !empty($this->executive_summary) && 
               !empty($this->achievements);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Unpublished' => 'bg-gray-100 text-gray-800',
            'Submitted' => 'bg-blue-100 text-blue-800',
            'Under Review' => 'bg-yellow-100 text-yellow-800',
            'Approved' => 'bg-green-100 text-green-800',
            'Rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get quarter display name
     */
    public function getQuarterNameAttribute(): string
    {
        return match($this->quarter) {
            'Q1' => 'First Quarter (Jan-Mar)',
            'Q2' => 'Second Quarter (Apr-Jun)',
            'Q3' => 'Third Quarter (Jul-Sep)',
            'Q4' => 'Fourth Quarter (Oct-Dec)',
            default => $this->quarter
        };
    }

    /**
     * Get overall performance rating with description
     */
    public function getRatingDescriptionAttribute(): string
    {
        if (!$this->overall_rating) {
            return 'Not Rated';
        }

        return match(true) {
            $this->overall_rating >= 4.5 => 'Outstanding',
            $this->overall_rating >= 3.5 => 'Very Good',
            $this->overall_rating >= 2.5 => 'Good',
            $this->overall_rating >= 1.5 => 'Fair',
            default => 'Needs Improvement'
        };
    }

    /**
     * Submit the report
     */
    public function submit(): bool
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }

        $this->status = 'Submitted';
        $this->submission_date = now();
        return $this->save();
    }

    /**
     * Approve the report
     */
    public function approve(User $approver, ?string $comments = null): bool
    {
        $this->status = 'Approved';
        $this->approval_date = now();
        $this->approved_by = $approver->id;
        if ($comments) {
            $this->reviewer_comments = $comments;
        }
        return $this->save();
    }

    /**
     * Reject the report
     */
    public function reject(string $reason): bool
    {
        $this->status = 'Rejected';
        $this->reviewer_comments = $reason;
        return $this->save();
    }
}
