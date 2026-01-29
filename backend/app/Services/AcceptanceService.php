<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcceptanceService
{
    public function __construct(private readonly ReputationService $reputation)
    {
    }

    public function acceptAnswer(Question $question, Answer $answer, User $actor): array
    {
        if ($question->user_id !== $actor->id) {
            throw new AuthorizationException('Only the question author can accept an answer.');
        }

        if ($answer->question_id !== $question->id) {
            throw new InvalidArgumentException('Answer does not belong to the question.');
        }

        return DB::transaction(function () use ($question, $answer, $actor): array {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $lockedQuestion->load('acceptedAnswer');

            $previousAnswer = $lockedQuestion->acceptedAnswer;
            $affectedUsers = [];

            if ($previousAnswer && $previousAnswer->id === $answer->id) {
                return [
                    'accepted_answer_id' => $lockedQuestion->accepted_answer_id,
                    'affected_user_ids' => $affectedUsers,
                ];
            }

            if ($previousAnswer) {
                $this->reputation->rollbackEvent($this->eventAttributes($previousAnswer, $actor));
                $affectedUsers[] = $previousAnswer->user_id;
            }

            $lockedQuestion->accepted_answer_id = $answer->id;
            $lockedQuestion->save();

            $this->reputation->applyEvent($this->eventAttributes($answer, $actor), 15);
            $affectedUsers[] = $answer->user_id;

            return [
                'accepted_answer_id' => $lockedQuestion->accepted_answer_id,
                'affected_user_ids' => array_values(array_unique($affectedUsers)),
            ];
        });
    }

    public function unacceptAnswer(Question $question, User $actor): array
    {
        if ($question->user_id !== $actor->id) {
            throw new AuthorizationException('Only the question author can unaccept an answer.');
        }

        return DB::transaction(function () use ($question, $actor): array {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $lockedQuestion->load('acceptedAnswer');

            $acceptedAnswer = $lockedQuestion->acceptedAnswer;

            if (! $acceptedAnswer) {
                return [
                    'accepted_answer_id' => null,
                    'affected_user_ids' => [],
                ];
            }

            $lockedQuestion->accepted_answer_id = null;
            $lockedQuestion->save();

            $this->reputation->rollbackEvent($this->eventAttributes($acceptedAnswer, $actor));

            return [
                'accepted_answer_id' => null,
                'affected_user_ids' => [$acceptedAnswer->user_id],
            ];
        });
    }

    private function eventAttributes(Answer $answer, User $actor): array
    {
        return [
            'user_id' => $answer->user_id,
            'actor_user_id' => $actor->id,
            'subject_type' => $answer->getMorphClass(),
            'subject_id' => $answer->getKey(),
            'event_type' => ReputationEvent::TYPE_ACCEPTED,
        ];
    }
}
