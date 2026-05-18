<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportReport extends Model
{
    /** @var list<string> Allowed values for Developer Support intake (must match form + validation). */
    public const REPORT_TYPES = [
        'Bug Report',
        'Feature Request',
        'Data/Report Issue',
        'Access Issue',
        'Other Concern',
    ];

    protected $fillable = [
        'user_id',
        'report_type',
        'title',
        'description',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repairTicket(): HasOne
    {
        return $this->hasOne(RepairTicket::class, 'report_id');
    }
}
