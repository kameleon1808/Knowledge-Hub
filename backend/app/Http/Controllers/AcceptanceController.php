<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Services\AcceptanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcceptanceController extends Controller
{
    public function __construct(private readonly AcceptanceService $acceptance)
    {
    }

    public function store(Request $request, Question $question, Answer $answer): JsonResponse
    {
        $this->authorize('accept', $question);

        if ($answer->question_id !== $question->id) {
            abort(422, 'Answer does not belong to the question.');
        }

        $result = $this->acceptance->acceptAnswer($question, $answer, $request->user());

        return response()->json([
            'question_id' => $question->id,
            'accepted_answer_id' => $result['accepted_answer_id'],
            'reputation' => $this->reputationPayload($result['affected_user_ids']),
        ]);
    }

    public function destroy(Request $request, Question $question): JsonResponse
    {
        $this->authorize('accept', $question);

        $result = $this->acceptance->unacceptAnswer($question, $request->user());

        return response()->json([
            'question_id' => $question->id,
            'accepted_answer_id' => $result['accepted_answer_id'],
            'reputation' => $this->reputationPayload($result['affected_user_ids']),
        ]);
    }

    private function reputationPayload(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'reputation'])
            ->mapWithKeys(fn (User $user) => [$user->id => $user->reputation])
            ->all();
    }
}
