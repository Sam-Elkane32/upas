<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KPI extends Model
{
    use HasFactory;

    protected $table = 'k_p_i_s'; // Explicitly define table name

    protected $fillable = [
        'kra_id',
        'code',
        'title',
        'description',
        'campus_code',
        'responsible_unit',
        'measurement_unit',
        'target_q1',
        'target_q2',
        'target_q3',
        'target_q4',
        'target_total',
        'accomplishment_q1',
        'accomplishment_q2',
        'accomplishment_q3',
        'accomplishment_q4',
        'accomplishment_total',
        'variance',
        'rate_of_accomplishment',
        'descriptive_rating',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_q1' => 'decimal:2',
        'target_q2' => 'decimal:2',
        'target_q3' => 'decimal:2',
        'target_q4' => 'decimal:2',
        'target_total' => 'decimal:2',
        'accomplishment_q1' => 'decimal:2',
        'accomplishment_q2' => 'decimal:2',
        'accomplishment_q3' => 'decimal:2',
        'accomplishment_q4' => 'decimal:2',
        'accomplishment_total' => 'decimal:2',
        'variance' => 'decimal:2',
        'rate_of_accomplishment' => 'decimal:2',
    ];

    public function kra(): BelongsTo
    {
        return $this->belongsTo(KRA::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessor for title (if needed for backward compatibility)
    public function getTitleAttribute()
    {
        return $this->attributes['title'] ?? $this->attributes['key_performance_indicator'] ?? '';
    }

    // Accessor for description (if needed for backward compatibility)
    public function getDescriptionAttribute()
    {
        return $this->attributes['description'] ?? $this->attributes['key_performance_indicator'] ?? '';
    }

    // Accessor for responsible unit
    public function getResponsibleUnitAttribute()
    {
        return $this->attributes['responsible_unit'] ?? $this->attributes['responsible_work_units'] ?? '';
    }

    // Accessor for measurement unit
    public function getMeasurementUnitAttribute()
    {
        return $this->attributes['measurement_unit'] ?? 'Units';
    }
}