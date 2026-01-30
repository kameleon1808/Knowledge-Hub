<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportService
{
    public function __construct()
    {
    }

    public function generateMarkdown(Project $project): string
    {
        $project->load(['knowledgeItems' => fn ($q) => $q->orderBy('title')]);

        $lines = [];
        $lines[] = '# ' . $this->escapeMd($project->name);
        $lines[] = '';

        if ($project->description) {
            $lines[] = $this->escapeMd($project->description);
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '';

        foreach ($project->knowledgeItems as $item) {
            $lines[] = '## ' . $this->escapeMd($item->title);
            $lines[] = '';
            $lines[] = '- **Type:** ' . $item->type;
            if ($item->source_meta) {
                foreach ($item->source_meta as $key => $val) {
                    if ($val !== null && $val !== '') {
                        $lines[] = '- **' . ucfirst(str_replace('_', ' ', $key)) . ':** ' . $this->escapeMd((string) $val);
                    }
                }
            }
            $lines[] = '';

            $text = $item->raw_text ?? '';
            if ($text !== '') {
                $lines[] = $this->escapeMdBlock($text);
            } else {
                $lines[] = '*No content extracted.*';
            }

            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        $content = implode("\n", $lines);
        $path = sprintf('exports/%s/%s.md', $project->id, Str::uuid()->toString());
        Storage::disk('local')->put($path, $content);

        return $path;
    }

    public function generatePdf(Project $project): string
    {
        $project->load(['knowledgeItems' => fn ($q) => $q->orderBy('title')]);

        $html = $this->buildExportHtml($project);
        $path = sprintf('exports/%s/%s.pdf', $project->id, Str::uuid()->toString());
        $fullPath = Storage::disk('local')->path($path);

        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($fullPath, $dompdf->output());

        return $path;
    }

    private function escapeMd(string $s): string
    {
        return str_replace(['\\', '`', '*', '_', '#', '[', ']'], ['\\\\', '\\`', '\\*', '\\_', '\\#', '\\[', '\\]'], $s);
    }

    private function escapeMdBlock(string $s): string
    {
        return $s;
    }

    private function buildExportHtml(Project $project): string
    {
        $title = htmlspecialchars($project->name, ENT_QUOTES, 'UTF-8');
        $desc = $project->description ? htmlspecialchars($project->description, ENT_QUOTES, 'UTF-8') : '';

        $body = "<h1>{$title}</h1>";
        if ($desc !== '') {
            $body .= '<p>' . nl2br($desc) . '</p><hr/>';
        }

        foreach ($project->knowledgeItems as $item) {
            $itemTitle = htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
            $body .= "<h2>{$itemTitle}</h2>";
            $body .= '<p><strong>Type:</strong> ' . htmlspecialchars($item->type, ENT_QUOTES, 'UTF-8') . '</p>';

            $text = $item->raw_text ?? '';
            if ($text !== '') {
                $body .= '<div>' . nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . '</div>';
            } else {
                $body .= '<p><em>No content extracted.</em></p>';
            }
            $body .= '<hr/>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;padding:20px;line-height:1.5;} h1{font-size:1.5em;} h2{font-size:1.2em;margin-top:1em;}</style></head><body>' . $body . '</body></html>';
    }
}
