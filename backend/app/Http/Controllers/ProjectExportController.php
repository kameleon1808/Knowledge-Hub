<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportController extends Controller
{
    public function __construct(
        private readonly ExportService $export
    ) {
    }

    public function markdown(Request $request, Project $project): StreamedResponse
    {
        $this->authorize('export', $project);

        $path = $this->export->generateMarkdown($project);
        app(ActivityLogger::class)->log(ActivityLogger::ACTION_EXPORT_GENERATED, $project, $request->user(), null, null, ['format' => 'markdown']);
        $filename = sprintf('%s-knowledge-base.md', \Illuminate\Support\Str::slug($project->name));

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'text/markdown',
        ]);
    }

    public function pdf(Request $request, Project $project): StreamedResponse
    {
        $this->authorize('export', $project);

        $path = $this->export->generatePdf($project);
        app(ActivityLogger::class)->log(ActivityLogger::ACTION_EXPORT_GENERATED, $project, $request->user(), null, null, ['format' => 'pdf']);
        $filename = sprintf('%s-knowledge-base.pdf', \Illuminate\Support\Str::slug($project->name));

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
