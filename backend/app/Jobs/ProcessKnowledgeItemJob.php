<?php

namespace App\Jobs;

use App\Models\KnowledgeItem;
use App\Services\Knowledge\KnowledgeProcessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKnowledgeItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        private readonly int $knowledgeItemId
    ) {
        $this->onQueue('default');
    }

    public function handle(KnowledgeProcessService $processor): void
    {
        $item = KnowledgeItem::find($this->knowledgeItemId);
        if ($item === null || $item->status !== KnowledgeItem::STATUS_PENDING) {
            return;
        }

        $processor->process($item);
    }
}
