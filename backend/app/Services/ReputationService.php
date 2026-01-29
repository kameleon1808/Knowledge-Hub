<?php

namespace App\Services;

use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReputationService
{
    public function applyEvent(array $attributes, int $points, ?array $metadata = null): bool
    {
        return DB::transaction(function () use ($attributes, $points, $metadata): bool {
            $user = User::query()->whereKey($attributes['user_id'])->lockForUpdate()->firstOrFail();

            try {
                ReputationEvent::create([
                    ...$attributes,
                    'points' => $points,
                    'metadata' => $metadata,
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueViolation($e)) {
                    return false;
                }

                throw $e;
            }

            $user->increment('reputation', $points);

            return true;
        });
    }

    public function rollbackEvent(array $attributes): bool
    {
        return DB::transaction(function () use ($attributes): bool {
            $event = ReputationEvent::query()
                ->where($attributes)
                ->lockForUpdate()
                ->first();

            if (! $event) {
                return false;
            }

            $user = User::query()->whereKey($event->user_id)->lockForUpdate()->first();

            if (! $user) {
                return false;
            }

            $points = $event->points;
            $event->delete();
            $user->increment('reputation', -$points);

            return true;
        });
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000';
    }
}
