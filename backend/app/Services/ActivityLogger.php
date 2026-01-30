<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;

class ActivityLogger
{
    public const ACTION_PROJECT_CREATED = 'project.created';
    public const ACTION_KNOWLEDGE_ITEM_UPLOADED = 'knowledge_item.uploaded';
    public const ACTION_KNOWLEDGE_ITEM_PROCESSED = 'knowledge_item.processed';
    public const ACTION_KNOWLEDGE_ITEM_FAILED = 'knowledge_item.failed';
    public const ACTION_EXPORT_GENERATED = 'export.generated';
    public const ACTION_RAG_ASKED = 'rag.asked';

    public function log(
        string $action,
        ?Project $project = null,
        ?Authenticatable $actor = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null
    ): ActivityLog {
        return ActivityLog::create([
            'actor_user_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'project_id' => $project?->id,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
