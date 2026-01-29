<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;

class AnswerPolicy
{
    public function create(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isMember();
    }

    public function update(User $user, Answer $answer): bool
    {
        return $user->isAdmin()
            || $user->isModerator()
            || $answer->user_id === $user->id;
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $user->isAdmin()
            || $user->isModerator()
            || $answer->user_id === $user->id;
    }
}
