<?php

namespace App\Services;

use App\Jobs\ProcessKnowledgeItemJob;
use App\Models\KnowledgeItem;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeIngestService
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    private const EXTENSION_MAP = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
    ];

    public function createFromFile(Project $project, UploadedFile $file): KnowledgeItem
    {
        $mime = $file->getClientMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Unsupported file type. Allowed: PDF, DOCX, TXT.');
        }

        $ext = self::EXTENSION_MAP[$mime] ?? $file->getClientOriginalExtension();
        $path = sprintf(
            'knowledge/%s/%s.%s',
            $project->id,
            Str::uuid()->toString(),
            $ext
        );

        $stored = $file->storeAs(
            dirname($path),
            basename($path),
            ['disk' => 'local']
        );

        if ($stored === false) {
            throw new \RuntimeException('Failed to store file.');
        }

        $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if ($title === '') {
            $title = 'Document ' . substr(Str::uuid()->toString(), 0, 8);
        }

        $item = KnowledgeItem::create([
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_DOCUMENT,
            'title' => $title,
            'source_meta' => [
                'filename' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => $file->getSize(),
            ],
            'original_content_path' => $stored,
            'raw_text' => null,
            'status' => KnowledgeItem::STATUS_PENDING,
            'error_message' => null,
        ]);

        ProcessKnowledgeItemJob::dispatch($item->id);

        return $item;
    }

    public function createFromEmail(Project $project, array $data): KnowledgeItem
    {
        $rawText = trim((string) ($data['body_text'] ?? ''));
        if ($rawText === '') {
            throw new \InvalidArgumentException('Email body is required.');
        }

        $item = KnowledgeItem::create([
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_EMAIL,
            'title' => $data['title'] ?? 'Email',
            'source_meta' => [
                'from' => $data['from'] ?? null,
                'sent_at' => $data['sent_at'] ?? null,
            ],
            'original_content_path' => null,
            'raw_text' => $rawText,
            'status' => KnowledgeItem::STATUS_PENDING,
            'error_message' => null,
        ]);

        ProcessKnowledgeItemJob::dispatch($item->id);

        return $item;
    }
}
