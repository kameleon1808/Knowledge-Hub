<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteVoteRequest;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Services\VoteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Relations\Relation;

class VoteController extends Controller
{
    public function __construct(private readonly VoteService $votes)
    {
    }

    public function store(StoreVoteRequest $request): JsonResponse
    {
        $votable = $this->resolveVotable(
            $request->string('votable_type')->toString(),
            $request->integer('votable_id')
        );

        $this->authorize('vote', $votable);

        $currentVote = $this->votes->castVote($request->user(), $votable, $request->integer('value'));

        return $this->voteResponse($votable, $currentVote);
    }

    public function destroy(DeleteVoteRequest $request): JsonResponse
    {
        $votable = $this->resolveVotable(
            $request->string('votable_type')->toString(),
            $request->integer('votable_id')
        );

        $this->authorize('vote', $votable);

        $this->votes->removeVote($request->user(), $votable);

        return $this->voteResponse($votable, null);
    }

    private function resolveVotable(string $type, int $id): Model
    {
        $modelClass = Relation::getMorphedModel($type);

        if (! $modelClass || ! in_array($modelClass, [Question::class, Answer::class], true)) {
            abort(422, 'Invalid votable type.');
        }

        return $modelClass::query()->findOrFail($id);
    }

    private function voteResponse(Model $votable, ?int $currentVote): JsonResponse
    {
        $score = (int) $votable->votes()->sum('value');
        $author = $votable->author()->first();

        return response()->json([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
            'score' => $score,
            'current_user_vote' => $currentVote,
            'reputation' => $author ? [$author->id => $author->reputation] : [],
        ]);
    }
}
