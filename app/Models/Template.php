<?php

namespace App\Models;

use App\Support\TemplateTableGrid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'sg_code',
        'template_code',
        'kra_title',
        'kpi_title',
        'fields_json',
        'status',
        'created_by',
        'campus_code',
        'campus_codes',
        'assigned_user_id',
        'is_locked',
        'locked_at',
        'locked_by',
        'lock_reason',
    ];

    protected $casts = [
        'fields_json'  => 'array',
        'campus_codes' => 'array',
        'is_locked'    => 'boolean',
        'locked_at'    => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'template_code', 'template_code');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'template_id', 'id');
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this template (Planning Coordinator) - legacy single assignee.
     * Prefer assignedUsers() for multi-assign; this returns the first assigned user for backward compatibility.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get all Planning Coordinators assigned to this template (multi-assign).
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'template_assigned_users', 'template_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check whether a user is assigned to this template (multi-assign or legacy single).
     */
    public function isAssignedToUser($userId): bool
    {
        $userId = (string) $userId;
        if ($this->relationLoaded('assignedUsers') && $this->assignedUsers->isNotEmpty()) {
            return $this->assignedUsers->contains(fn ($u) => (string) $u->id === $userId);
        }
        $ids = $this->assignedUsers()->pluck('users.id')->toArray();
        if (!empty($ids)) {
            return in_array((int) $userId, $ids, true) || in_array($userId, array_map('strval', $ids), true);
        }
        return (string) $this->assigned_user_id === $userId;
    }

    /**
     * Get template by code
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('template_code', $code)->first();
    }

    /**
     * Get template schema fields
     */
    public function getSchemaFields(): array
    {
        return $this->fields_json['fields'] ?? [];
    }

    /**
     * Expand fields so 2+ subheaders become separate columns (keys: parentKey_subLabel).
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public static function expandSchemaForDataGrid(array $fields): array
    {
        $getKey = static function (array $f): string {
            $k = $f['key'] ?? $f['name'] ?? null;
            if ($k !== null && $k !== '') {
                return (string) $k;
            }
            $label = $f['label'] ?? '';
            $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $label)));

            return trim($normalized, '_');
        };

        [$expanded] = TemplateTableGrid::expandFieldsWithSubheaderGroups($fields, $getKey);

        return $expanded;
    }

    /**
     * Get template summary row rules
     */
    public function getSummaryRules(): array
    {
        return $this->fields_json['summary_rules'] ?? [];
    }

    /**
     * Per blue-cell formula metadata (target_field, sourceA, row_indices, …) — Super Admin uses this with summary_rules; merge for PC/backend parity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSummaryCellMappings(): array
    {
        $m = $this->fields_json['summary_cell_mappings'] ?? [];

        return is_array($m) ? $m : [];
    }

    /**
     * Check if template can be edited
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'Unpublished';
    }

    /**
     * Fixed subject line for super-admin deadline notifications (not user-editable).
     * Template code first (e.g. T1) so it scans quickly in inbox and dashboard.
     */
    public function fixedDeadlineNotificationTitle(): string
    {
        $code = trim((string) ($this->template_code ?? ''));
        if ($code === '') {
            $code = 'Template';
        }

        return $code . ' Template Reminder';
    }

    /**
     * Whether this template is for all campuses (no restriction).
     * Matches scopeForCampus: both campus_code and campus_codes must be empty / unset.
     */
    public function allowsAllCampuses(): bool
    {
        $codes = $this->campus_codes;
        if (is_array($codes) && count($codes) > 0) {
            return false;
        }
        $cc = $this->campus_code;
        if ($cc !== null && trim((string) $cc) !== '') {
            return false;
        }

        return true;
    }

    /**
     * Whether a given campus code can access this template.
     */
    public function allowsCampus(string $campusCode): bool
    {
        if ($this->allowsAllCampuses()) {
            return true;
        }
        $normalized = strtoupper(trim($campusCode));
        $codes = $this->campus_codes;
        if (is_array($codes)) {
            foreach ($codes as $c) {
                if (strtoupper(trim((string) $c)) === $normalized) {
                    return true;
                }
            }
        }
        $legacy = strtoupper(trim((string) ($this->campus_code ?? '')));

        return $legacy !== '' && $legacy === $normalized;
    }

    /**
     * Scope: templates that the given campus code can access.
     * null / empty campus_codes = all campuses; otherwise stored JSON (longText) must include the code, or campus_code matches.
     * Uses LIKE instead of json_* functions so older MySQL/MariaDB without JSON functions still work.
     */
    public function scopeForCampus($query, string $campusCode): void
    {
        $normalized = strtoupper(trim($campusCode));
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalized);
        $likePattern = '%"' . $escaped . '"%';

        $query->where(function ($q) use ($normalized, $likePattern) {
            $q->where(function ($allCampuses) {
                $allCampuses->where(function ($emptyCodes) {
                    $emptyCodes->whereNull('campus_codes')
                        ->orWhere('campus_codes', '')
                        ->orWhere('campus_codes', '[]');
                })->where(function ($noLegacyCampus) {
                    $noLegacyCampus->whereNull('campus_code')
                        ->orWhere('campus_code', '');
                });
            })
                ->orWhere('campus_codes', 'like', $likePattern)
                ->orWhere('campus_code', $normalized);
        });
    }

    /**
     * Scope: templates assigned to the given user (multi-assign or legacy single).
     */
    public function scopeAssignedToUser($query, $userId): void
    {
        $query->where(function ($q) use ($userId) {
            $q->whereHas('assignedUsers', fn ($sub) => $sub->where('users.id', $userId))
                ->orWhere('assigned_user_id', $userId);
        });
    }

    // ─── Lock / Unlock ────────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function lock(int $userId, string $reason = ''): void
    {
        $this->update([
            'is_locked'   => true,
            'locked_at'   => now(),
            'locked_by'   => $userId,
            'lock_reason' => $reason,
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'is_locked'   => false,
            'locked_at'   => null,
            'locked_by'   => null,
            'lock_reason' => null,
        ]);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}