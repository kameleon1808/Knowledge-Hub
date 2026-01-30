<?php

namespace App\AI\Exceptions;

use Exception;

class NotConfigured extends Exception
{
    public static function missingApiKey(string $provider): self
    {
        return new self(
            "AI is enabled but the configured provider ({$provider}) has no API key set. " .
            'Please set the corresponding key in .env or disable AI.'
        );
    }

    public static function aiDisabled(): self
    {
        return new self(
            'AI features are disabled. Set AI_ENABLED=true and configure a provider to use AI.'
        );
    }
}
