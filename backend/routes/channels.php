<?php

use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register event broadcasting channel authorization callbacks.
| Question channels: authenticated users who can view the question.
| User notifications: only that user.
|
*/

Broadcast::channel('question.{questionId}', function (User $user, int $questionId): bool {
    $question = Question::find($questionId);

    return $question !== null;
});

Broadcast::channel('user.{userId}.notifications', function (User $user, int $userId): bool {
    return (int) $user->id === (int) $userId;
});
