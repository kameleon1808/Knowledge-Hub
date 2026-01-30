<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeItem;
use App\Services\ActivityLogger;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KnowledgeProcessService
{
    public function __construct(
        private readonly TextExtractionService $extractor,
        private readonly ChunkingService $chunker,
        private readonly EmbeddingService $embedding,
        private readonly ActivityLogger $activity
    ) {
    }

    public function process(KnowledgeItem $item): void
    {
        $item->load('project');

        try {
            $rawText = $this->getRawText($item);
            $normalized = $this->extractor->normalize($rawText);

            if ($normalized === '') {
                $item->update([
                    'status' => KnowledgeItem::STATUS_FAILED,
                    'error_message' => 'No text content extracted.',
                ]);

                return;
            }

            $item->update([
                'raw_text' => $normalized,
                'status' => KnowledgeItem::STATUS_PENDING,
                'error_message' => null,
            ]);

            $chunks = $this->chunker->chunk($normalized);
            if ($chunks === []) {
                $item->update([
                    'status' => KnowledgeItem::STATUS_PROCESSED,
                    'error_message' => null,
                ]);

                return;
            }

            $auditContext = [
                'subject_type' => KnowledgeItem::class,
                'subject_id' => $item->id,
            ];

            $texts = array_column($chunks, 'text');
            $result = $this->embedding->embed($texts, $auditContext);
            $vectors = $result->vectors;

            if (count($vectors) !== count($chunks)) {
                throw new \RuntimeException('Embedding count does not match chunk count.');
            }

            $item->chunks()->delete();

            $now = now();
            $driver = DB::connection()->getDriverName();
            $isPgsql = $driver === 'pgsql';

            foreach ($chunks as $index => $chunkData) {
                $vec = $vectors[$index] ?? null;
                if ($vec === null) {
                    continue;
                }
                $embeddingStr = '[' . implode(',', array_map(fn ($x) => (float) $x, $vec)) . ']';
                $tokensCount = $result->totalTokens ? (int) ($result->totalTokens / count($chunks)) : null;

                if ($isPgsql) {
                    DB::insert(
                        'INSERT INTO knowledge_chunks (knowledge_item_id, chunk_index, content_text, content_hash, embedding, tokens_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?::vector, ?, ?, ?)',
                        [$item->id, $index, $chunkData['text'], $chunkData['hash'], $embeddingStr, $tokensCount, $now, $now]
                    );
                } else {
                    DB::table('knowledge_chunks')->insert([
                        'knowledge_item_id' => $item->id,
                        'chunk_index' => $index,
                        'content_text' => $chunkData['text'],
                        'content_hash' => $chunkData['hash'],
                        'embedding' => $embeddingStr,
                        'tokens_count' => $tokensCount,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $item->update([
                'status' => KnowledgeItem::STATUS_PROCESSED,
                'error_message' => null,
            ]);
            $this->activity->log(
                ActivityLogger::ACTION_KNOWLEDGE_ITEM_PROCESSED,
                $item->project,
                null,
                KnowledgeItem::class,
                $item->id,
                ['title' => $item->title]
            );
        } catch (\Throwable $e) {
            Log::error('Knowledge item processing failed', [
                'knowledge_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            $item->update([
                'status' => KnowledgeItem::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            $this->activity->log(
                ActivityLogger::ACTION_KNOWLEDGE_ITEM_FAILED,
                $item->project,
                null,
                KnowledgeItem::class,
                $item->id,
                ['title' => $item->title, 'error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    private function getRawText(KnowledgeItem $item): string
    {
        if ($item->type === KnowledgeItem::TYPE_EMAIL) {
            return $item->raw_text ?? '';
        }

        return $this->extractor->extract($item);
    }
}
