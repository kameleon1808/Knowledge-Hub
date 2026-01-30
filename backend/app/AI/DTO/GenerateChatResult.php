<?php

namespace App\AI\DTO;

use App\Models\AiAuditLog;

final class GenerateChatResult
{
    public function __construct(
        public readonly ChatResponse $response,
        public readonly AiAuditLog $auditLog
    ) {
    }

    public static function make(ChatResponse $response, AiAuditLog $auditLog): self
    {
        return new self($response, $auditLog);
    }
}
