<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users in this campus
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'campus', 'code');
    }

    /**
     * Get campus admins
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campus_admins', 'campus_id', 'admin_user_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Get primary admin for this campus
     */
    public function primaryAdmin()
    {
        return $this->admins()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get active users in this campus
     */
    public function activeUsers(): HasMany
    {
        return $this->users()->where('is_active', true);
    }

    /**
     * Get form submissions for this campus
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'campus_code', 'code');
    }

    /**
     * Get campus statistics
     */
    public function getStatsAttribute()
    {
        $users = $this->users();
        $activeUsers = $this->activeUsers();
        
        return [
            'total_users' => $users->count(),
            'active_users' => $activeUsers->count(),
            'admins_count' => $this->admins()->count(),
            'creator_editors_count' => $activeUsers->where('role', 'creator_editor')->count(),
        ];
    }
}