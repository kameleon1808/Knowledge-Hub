<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user);
    }

    public function addKnowledge(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user) || $project->hasMember($user);
    }

    public function askRag(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->hasMember($user);
    }

    public function export(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->hasMember($user);
    }
}
