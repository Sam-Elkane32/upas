<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_title',
        'division',
        'sg_code',
        'strategic_goal',
        'kra_title',
        'kpi_title',
        'responsible_unit',
        'kra_kpi_data',
        'target_q1',
        'target_q2',
        'target_q3',
        'target_q4',
        'target_total',
        'template_id',
        'template_code',
        'status',
        'created_by',
        'campus_code',
    ];

    protected $casts = [
        'target_q1' => 'decimal:2',
        'target_q2' => 'decimal:2',
        'target_q3' => 'decimal:2',
        'target_q4' => 'decimal:2',
        'target_total' => 'decimal:2',
        'kra_kpi_data' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_code', 'template_code');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Forms that view-only users should see: published, or tied to at least one published template
     * (many campuses leave the form row Unpublished while templates are Published).
     */
    public function scopeReadableByViewOnly(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('forms.status', 'Published')
                ->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('templates')
                        ->whereColumn('templates.form_id', 'forms.id')
                        ->where('templates.status', 'Published')
                        ->whereNotNull('templates.form_id');
                });
        });
    }

    public function isReadableByViewOnly(): bool
    {
        if (($this->status ?? '') === 'Published') {
            return true;
        }

        return $this->templates()->where('status', 'Published')->exists();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'form_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    public function newSubmissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'form_id');
    }

    /**
     * Get published forms for a specific campus
     */
    public static function getPublishedForCampus($campusCode)
    {
        return static::where('campus_code', $campusCode)
            ->where('status', 'Published')
            ->with('template')
            ->get();
    }

    /**
     * Number of KRAs on this form (same rules as Forms list in super-admin/templates).
     */
    public function getKraCount(): int
    {
        $kraKpi = $this->kra_kpi_data;
        if (is_string($kraKpi)) {
            $decoded = json_decode($kraKpi, true);
            $kraKpi = is_array($decoded) ? $decoded : [];
        }
        if (is_array($kraKpi) && count($kraKpi) > 0) {
            return count($kraKpi);
        }
        if ($this->kra_title) {
            return count(array_filter(array_map('trim', preg_split('/\s*;\s*/', (string) $this->kra_title))));
        }

        return 0;
    }

    /**
     * Number of KPIs on this form (same rules as Forms list in super-admin/templates).
     */
    public function getKpiCount(): int
    {
        $kraKpi = $this->kra_kpi_data;
        if (is_string($kraKpi)) {
            $decoded = json_decode($kraKpi, true);
            $kraKpi = is_array($decoded) ? $decoded : [];
        }
        if (is_array($kraKpi) && count($kraKpi) > 0) {
            $kpiCount = 0;
            foreach ($kraKpi as $kra) {
                $kpiCount += isset($kra['kpis']) && is_array($kra['kpis']) ? count($kra['kpis']) : 0;
            }

            return $kpiCount;
        }
        if ($this->kpi_title) {
            $parts = array_filter(array_map('trim', preg_split('/\s*;\s*/', (string) $this->kpi_title)));

            return count($parts);
        }

        return 0;
    }

    /**
     * Auto-calculate target total
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            $model->target_total = $model->target_q1 + $model->target_q2 + $model->target_q3 + $model->target_q4;
        });
    }
}