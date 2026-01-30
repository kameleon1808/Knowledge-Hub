<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKnowledgeDocumentRequest;
use App\Http\Requests\StoreKnowledgeEmailRequest;
use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\KnowledgeIngestService;
use Illuminate\Http\RedirectResponse;

class ProjectKnowledgeController extends Controller
{
    public function __construct(
        private readonly KnowledgeIngestService $ingest
    ) {
    }

    public function storeDocument(StoreKnowledgeDocumentRequest $request, Project $project): RedirectResponse
    {
        $file = $request->file('file');
        $item = $this->ingest->createFromFile($project, $file);
        app(ActivityLogger::class)->log(
            ActivityLogger::ACTION_KNOWLEDGE_ITEM_UPLOADED,
            $project,
            $request->user(),
            \App\Models\KnowledgeItem::class,
            $item->id,
            ['type' => 'document', 'title' => $item->title]
        );

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'knowledge'])
            ->with('success', 'Document uploaded. Processing will start shortly.');
    }

    public function storeEmail(StoreKnowledgeEmailRequest $request, Project $project): RedirectResponse
    {
        $item = $this->ingest->createFromEmail($project, [
            'title' => $request->string('title')->toString(),
            'from' => $request->input('from') ? $request->string('from')->toString() : null,
            'sent_at' => $request->input('sent_at') ? $request->string('sent_at')->toString() : null,
            'body_text' => $request->string('body_text')->toString(),
        ]);
        app(ActivityLogger::class)->log(
            ActivityLogger::ACTION_KNOWLEDGE_ITEM_UPLOADED,
            $project,
            $request->user(),
            \App\Models\KnowledgeItem::class,
            $item->id,
            ['type' => 'email', 'title' => $item->title]
        );

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'knowledge'])
            ->with('success', 'Email added. Processing will start shortly.');
    }
}
