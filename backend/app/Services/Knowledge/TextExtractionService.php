<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeItem;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as PhpWordIO;
use Smalot\PdfParser\Parser as SmalotPdfParser;

class TextExtractionService
{
    public function extract(KnowledgeItem $item): string
    {
        if ($item->type === KnowledgeItem::TYPE_EMAIL) {
            return $this->normalize($item->raw_text ?? '');
        }

        $path = $item->original_content_path;
        if ($path === null || $path === '') {
            throw new \RuntimeException('Document has no stored file path.');
        }

        $fullPath = Storage::disk('local')->path($path);
        if (! is_readable($fullPath)) {
            throw new \RuntimeException('Stored file is not readable.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $raw = match ($ext) {
            'txt' => $this->extractTxt($fullPath),
            'docx' => $this->extractDocx($fullPath),
            'pdf' => $this->extractPdf($fullPath),
            default => throw new \RuntimeException("Unsupported document extension: {$ext}."),
        };

        return $this->normalize($raw);
    }

    public function normalize(string $text): string
    {
        $text = trim($text);
        $text = str_replace("\0", '', $text);
        $text = (string) preg_replace('/[ \t]+/', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function extractTxt(string $path): string
    {
        $content = file_get_contents($path);

        return $content !== false ? $content : '';
    }

    private function extractDocx(string $path): string
    {
        $phpWord = PhpWordIO::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->extractPhpWordElementText($element);
            }
        }

        return $text;
    }

    /**
     * @param \PhpOffice\PhpWord\Element\AbstractElement $element
     */
    private function extractPhpWordElementText($element): string
    {
        if ($element instanceof Text) {
            return $element->getText();
        }

        if ($element instanceof TextBreak) {
            return "\n";
        }

        if ($element instanceof TextRun) {
            $run = '';
            foreach ($element->getElements() as $child) {
                $run .= $this->extractPhpWordElementText($child);
            }

            return $run;
        }

        if (method_exists($element, 'getElements')) {
            $block = '';
            foreach ($element->getElements() as $child) {
                $block .= $this->extractPhpWordElementText($child);
            }

            return $block;
        }

        return '';
    }

    private function extractPdf(string $path): string
    {
        $parser = new SmalotPdfParser;
        $pdf = $parser->parseFile($path);

        return $pdf->getText() ?? '';
    }
}
