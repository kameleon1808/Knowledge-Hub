<?php

namespace App\AI\Exceptions;

use Exception;

class ProviderError extends Exception
{
    public static function requestFailed(string $provider, string $reason = ''): self
    {
        $message = "AI provider ({$provider}) request failed.";
        if ($reason !== '') {
            $message .= ' ' . $reason;
        }

        return new self($message);
    }
}
