<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'department',
        'position',
        'role',
        'campus',
        'campus_code',
        'phone_number',
        'is_active',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * User roles for the UPAS system
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_CREATOR_EDITOR = 'creator_editor';
    public const ROLE_VIEW_ONLY = 'view_only';
    public const ROLE_PLANNING_COORDINATOR = 'planning_coordinator';
    /** Beta / support: Messages-only access (see RestrictDeveloperAccess middleware). */
    public const ROLE_DEVELOPER = 'developer';

    /**
     * Get user's accomplishment plans
     */
    public function accomplishmentPlans()
    {
        return $this->hasMany(AccomplishmentPlan::class);
    }

    /**
     * Get submissions created by this user
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class, 'submitted_by');
    }

    /**
     * Get user's department information
     */
    public function departmentInfo()
    {
        return $this->belongsTo(Department::class, 'department', 'id');
    }

    /**
     * Get user's campus information
     */
    public function campusInfo()
    {
        return $this->belongsTo(Campus::class, 'campus_code', 'code');
    }

    /**
     * Get the user who approved this user
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is creator/editor
     */
    public function isCreatorEditor(): bool
    {
        return $this->hasRole(self::ROLE_CREATOR_EDITOR);
    }

    /**
     * Check if user is view-only
     */
    public function isViewOnly(): bool
    {
        return $this->hasRole(self::ROLE_VIEW_ONLY);
    }

    /**
     * Division-level view-only accounts (e.g. OP, OVPAA) use campus_code "DIVISION" and should see all campuses.
     */
    public function isDivisionLevelViewOnly(): bool
    {
        if (!$this->isViewOnly()) {
            return false;
        }

        return strtoupper(trim((string) ($this->campus_code ?? ''))) === 'DIVISION';
    }

    /**
     * View-only users tied to a real campus (not division-wide).
     */
    public function restrictsViewOnlyToSingleCampus(): bool
    {
        if (!$this->isViewOnly()) {
            return false;
        }

        $code = trim((string) ($this->campus_code ?? ''));

        return $code !== '' && strtoupper($code) !== 'DIVISION';
    }

    /**
     * Whether a view-only user may open a published form (division: all campuses; campus: own campus only).
     */
    public function viewOnlyCanAccessForm(Form $form): bool
    {
        if (!$this->isViewOnly() || !$form->isReadableByViewOnly()) {
            return false;
        }

        if ($this->restrictsViewOnlyToSingleCampus()) {
            return (string) $form->campus_code === (string) $this->campus_code;
        }

        return true;
    }

    /**
     * Developer / beta-support account: may only use internal messaging (plus profile / auth).
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole(self::ROLE_DEVELOPER);
    }

    /**
     * Check if user is planning coordinator
     * Checks both position and role. Creator/Editors act as Planning Coordinators for template assignment.
     */
    public function isPlanningCoordinator(): bool
    {
        return $this->hasRole(self::ROLE_PLANNING_COORDINATOR)
            || $this->hasRole(self::ROLE_CREATOR_EDITOR)
            || $this->position === 'Planning Coordinator'
            || $this->position === 'planning_coordinator'
            || $this->position === 'planning-coordinator';
    }

    /**
     * Check if user is QA Coordinator (can set Evidence Verified By The QA to Yes/No).
     */
    public function isQACoordinator(): bool
    {
        return $this->position === 'QA Coordinator'
            || $this->position === 'qa_coordinator'
            || $this->position === 'qa-coordinator';
    }

    /**
     * Get user's full name with employee ID
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->name} ({$this->employee_id})";
    }

    /**
     * Get quarterly reports created by this user
     */
    public function quarterlyReports()
    {
        return $this->hasMany(QuarterlyReport::class);
    }

    /**
     * Get quarterly reports approved by this user
     */
    public function approvedReports()
    {
        return $this->hasMany(QuarterlyReport::class, 'approved_by');
    }

    /**
     * Check if user can approve reports
     */
    public function canApproveReports(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    /**
     * Check if user can access all campuses
     */
    public function canAccessAllCampuses(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can access specific campus
     */
    public function canAccessCampus(string $campusCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return $this->campus_code === $campusCode;
    }

    /**
     * Check if user can create forms
     */
    public function canCreateForms(): bool
    {
        return $this->isCreatorEditor() || $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Check if user can approve forms
     */
    public function canApproveForms(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Get current quarter's report
     */
    public function getCurrentQuarterReport()
    {
        $currentQuarter = 'Q' . ceil(now()->month / 3);
        $currentYear = now()->year;
        
        return $this->quarterlyReports()
            ->where('year', $currentYear)
            ->where('quarter', $currentQuarter)
            ->first();
    }

    /**
     * Templates assigned to this user (Planning Coordinator multi-assign).
     */
    public function assignedTemplates()
    {
        return $this->belongsToMany(Template::class, 'template_assigned_users', 'user_id', 'template_id')
            ->withTimestamps();
    }

    public function supportReports()
    {
        return $this->hasMany(SupportReport::class);
    }

    public function assignedRepairTickets()
    {
        return $this->hasMany(RepairTicket::class, 'assigned_to_user_id');
    }
}
