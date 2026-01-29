<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Question $question): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isMember();
    }

    public function update(User $user, Question $question): bool
    {
        return $user->isAdmin()
            || $user->isModerator()
            || $question->user_id === $user->id;
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->isAdmin()
            || $user->isModerator()
            || $question->user_id === $user->id;
    }

    public function vote(User $user, Question $question): bool
    {
        return in_array($user->role, User::roles(), true)
            && $question->user_id !== $user->id;
    }

    public function accept(User $user, Question $question): bool
    {
        return $question->user_id === $user->id;
    }
}
