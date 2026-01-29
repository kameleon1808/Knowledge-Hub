<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoteService
{
    public function __construct(private readonly ReputationService $reputation)
    {
    }

    public function castVote(User $actor, Model $votable, int $value): ?int
    {
        if (! in_array($value, [1, -1], true)) {
            throw new InvalidArgumentException('Invalid vote value.');
        }

        if ($actor->id === ($votable->user_id ?? null)) {
            throw new AuthorizationException('You cannot vote on your own content.');
        }

        return DB::transaction(function () use ($actor, $votable, $value): ?int {
            $vote = Vote::query()
                ->where('user_id', $actor->id)
                ->where('votable_type', $votable->getMorphClass())
                ->where('votable_id', $votable->getKey())
                ->lockForUpdate()
                ->first();

            if ($vote && $vote->value === $value) {
                $event = $this->voteEvent($votable, $vote->value);
                $this->reputation->rollbackEvent($this->eventAttributes($votable, $actor, $event['type']));
                $vote->delete();

                return null;
            }

            if ($vote) {
                $previousEvent = $this->voteEvent($votable, $vote->value);
                $this->reputation->rollbackEvent($this->eventAttributes($votable, $actor, $previousEvent['type']));

                $vote->update(['value' => $value]);

                $newEvent = $this->voteEvent($votable, $value);
                $this->reputation->applyEvent(
                    $this->eventAttributes($votable, $actor, $newEvent['type']),
                    $newEvent['points']
                );

                return $value;
            }

            Vote::create([
                'user_id' => $actor->id,
                'votable_type' => $votable->getMorphClass(),
                'votable_id' => $votable->getKey(),
                'value' => $value,
            ]);

            $event = $this->voteEvent($votable, $value);
            $this->reputation->applyEvent(
                $this->eventAttributes($votable, $actor, $event['type']),
                $event['points']
            );

            return $value;
        });
    }

    public function removeVote(User $actor, Model $votable): bool
    {
        return DB::transaction(function () use ($actor, $votable): bool {
            $vote = Vote::query()
                ->where('user_id', $actor->id)
                ->where('votable_type', $votable->getMorphClass())
                ->where('votable_id', $votable->getKey())
                ->lockForUpdate()
                ->first();

            if (! $vote) {
                return false;
            }

            $event = $this->voteEvent($votable, $vote->value);
            $this->reputation->rollbackEvent($this->eventAttributes($votable, $actor, $event['type']));
            $vote->delete();

            return true;
        });
    }

    private function voteEvent(Model $votable, int $value): array
    {
        if ($value === -1) {
            return [
                'type' => ReputationEvent::TYPE_DOWNVOTE,
                'points' => -2,
            ];
        }

        if ($votable instanceof Question) {
            return [
                'type' => ReputationEvent::TYPE_UPVOTE_QUESTION,
                'points' => 5,
            ];
        }

        if ($votable instanceof Answer) {
            return [
                'type' => ReputationEvent::TYPE_UPVOTE_ANSWER,
                'points' => 10,
            ];
        }

        throw new InvalidArgumentException('Unsupported votable type.');
    }

    private function eventAttributes(Model $votable, User $actor, string $eventType): array
    {
        return [
            'user_id' => $votable->user_id ?? throw new InvalidArgumentException('Votable missing user_id.'),
            'actor_user_id' => $actor->id,
            'subject_type' => $votable->getMorphClass(),
            'subject_id' => $votable->getKey(),
            'event_type' => $eventType,
        ];
    }
}
