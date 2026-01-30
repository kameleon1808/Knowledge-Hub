<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users();
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(KnowledgeItem::class);
    }

    public function ragQueries(): HasMany
    {
        return $this->hasMany(RagQuery::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_user_id === $user->id;
    }

    public function hasMember(User $user): bool
    {
        if ($this->owner_user_id === $user->id) {
            return true;
        }

        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function memberRole(User $user): ?string
    {
        if ($this->owner_user_id === $user->id) {
            return self::ROLE_OWNER;
        }

        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Scope: only projects the user can see (admin sees all; others see owned or member of).
     */
    public function scopeVisibleTo($query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('projects.owner_user_id', $user->id)
                ->orWhereExists(function ($sub) use ($user) {
                    $sub->selectRaw(1)
                        ->from('project_user')
                        ->whereColumn('project_user.project_id', 'projects.id')
                        ->where('project_user.user_id', $user->id);
                });
        });
    }
}
