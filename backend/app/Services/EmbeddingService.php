<?php

namespace App\Services;

use App\AI\EmbeddingManager;
use App\AI\DTO\EmbeddingResult;

class EmbeddingService
{
    public function __construct(
        private readonly EmbeddingManager $manager
    ) {
    }

    /**
     * Embed one or more texts. All AI calls are audited.
     *
     * @param  array<int, string>  $texts
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $auditContext
     */
    public function embed(array $texts, array $auditContext): EmbeddingResult
    {
        return $this->manager->embedWithAudit($texts, $auditContext);
    }

    public function isConfigured(): bool
    {
        return $this->manager->isEnabled() && $this->manager->isConfigured();
    }
}
