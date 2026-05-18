<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'user_id',
        'strategic_goal',
        'kra',
        'kpi',
        'target_value',
        'actual_value',
        'justification',
        'file_path',
        'google_drive_link',
        'status',
        'admin_remarks',
        'approved_by',
        'approved_at',
        'returned_at',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    // Relationships
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'returned' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'returned' => 'Returned',
            default => 'Unknown',
        };
    }

    public function getAchievementPercentageAttribute(): float
    {
        if ($this->target_value == 0) {
            return 0;
        }
        return min(100, ($this->actual_value / $this->target_value) * 100);
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === 'draft' || $this->status === 'returned';
    }

    // Scopes
    public function scopePending($query)
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

    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}