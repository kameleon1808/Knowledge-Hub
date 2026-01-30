<?php

namespace App\Services\Knowledge;

class ChunkingService
{
    /** Chunk size in characters (target ~800–1200). */
    private const CHUNK_SIZE = 1000;

    /** Overlap in percent (10–15%). */
    private const OVERLAP_PERCENT = 12;

    /**
     * Split text into overlapping chunks. Each chunk is at most CHUNK_SIZE chars;
     * overlap is OVERLAP_PERCENT so context is preserved across boundaries.
     *
     * @return array<int, array{text: string, hash: string}>
     */
    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $overlap = (int) floor(self::CHUNK_SIZE * self::OVERLAP_PERCENT / 100);
        $step = self::CHUNK_SIZE - $overlap;
        $chunks = [];
        $offset = 0;
        $len = strlen($text);
        $index = 0;

        while ($offset < $len) {
            $piece = substr($text, $offset, self::CHUNK_SIZE);
            if ($piece === '') {
                break;
            }
            $piece = trim($piece);
            if ($piece !== '') {
                $chunks[] = [
                    'text' => $piece,
                    'hash' => hash('sha256', $piece),
                ];
                $index++;
            }
            $offset += $step;
        }

        return $chunks;
    }
}
