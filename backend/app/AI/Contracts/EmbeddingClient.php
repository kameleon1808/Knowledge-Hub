<?php

namespace App\AI\Contracts;

use App\AI\DTO\EmbeddingResult;

interface EmbeddingClient
{
    /**
     * @param  array<int, string>  $texts
     */
    public function embed(array $texts): EmbeddingResult;
}
