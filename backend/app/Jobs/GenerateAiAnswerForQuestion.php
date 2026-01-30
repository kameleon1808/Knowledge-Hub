<?php

namespace App\Jobs;

use App\Models\Question;
use App\Services\AiAnswerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class GenerateAiAnswerForQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Question $question
    ) {
        $this->onQueue('default');
    }

    public function handle(AiAnswerService $aiAnswerService): void
    {
        if (! Config::get('ai.enabled') || ! Config::get('ai.auto_answer')) {
            return;
        }

        if (! $aiAnswerService->isConfigured()) {
            return;
        }

        if ($aiAnswerService->questionHasAiAnswer($this->question)) {
            return;
        }

        $aiAnswerService->generateForQuestion($this->question, null);
    }
}
