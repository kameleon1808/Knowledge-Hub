<?php

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\User;

class BookmarkPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isMember();
    }

    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->user_id === $user->id;
    }
}
