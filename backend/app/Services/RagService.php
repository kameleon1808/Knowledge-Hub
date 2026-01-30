<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class RagService
{
    private const TOP_K = 8;

    public function __construct(
        private readonly EmbeddingService $embedding
    ) {
    }

    /**
     * Retrieve relevant chunks for a question (vector similarity, project-scoped).
     *
     * @return array<int, array{id: int, content_text: string, knowledge_item_id: int, item_title: string|null}>
     */
    public function retrieve(Project $project, string $questionText, array $auditContext): array
    {
        $texts = [trim($questionText)];
        if ($texts[0] === '') {
            return [];
        }

        $result = $this->embedding->embed($texts, $auditContext);
        $vector = $result->vectors[0] ?? null;
        if ($vector === null) {
            return [];
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return [];
        }

        $vectorStr = '[' . implode(',', array_map(fn ($x) => (float) $x, $vector)) . ']';

        $chunkIds = DB::select(
            "
            SELECT kc.id
            FROM knowledge_chunks kc
            INNER JOIN knowledge_items ki ON ki.id = kc.knowledge_item_id
            WHERE ki.project_id = ?
            ORDER BY kc.embedding <=> ?::vector
            LIMIT ?
            ",
            [$project->id, $vectorStr, self::TOP_K]
        );

        if ($chunkIds === []) {
            return [];
        }

        $ids = array_map(fn ($row) => $row->id, $chunkIds);
        $chunks = KnowledgeChunk::query()
            ->whereIn('id', $ids)
            ->with('knowledgeItem:id,title')
            ->get()
            ->sortBy(fn ($c) => (int) array_search($c->id, $ids, true))
            ->values();

        return $chunks->map(fn ($c) => [
            'id' => $c->id,
            'content_text' => $c->content_text,
            'knowledge_item_id' => $c->knowledge_item_id,
            'item_title' => $c->knowledgeItem?->title,
        ])->all();
    }

    /**
     * Build context string for the LLM from retrieved chunks (with short IDs for citation).
     *
     * @param  array<int, array{id: int, content_text: string, knowledge_item_id: int, item_title: string|null}>  $chunks
     */
    public function buildContextPrompt(array $chunks): string
    {
        if ($chunks === []) {
            return 'No relevant context found in the knowledge base.';
        }

        $lines = [];
        foreach ($chunks as $i => $chunk) {
            $shortId = '[' . ($i + 1) . ']';
            $title = $chunk['item_title'] ?? 'Document';
            $lines[] = "--- {$shortId} (from: {$title}) ---\n" . $chunk['content_text'];
        }

        return implode("\n\n", $lines);
    }
}
