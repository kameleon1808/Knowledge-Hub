<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isMember();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $user->isModerator() || $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }
}
