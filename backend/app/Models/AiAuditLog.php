<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAuditLog extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'subject_type',
        'subject_id',
        'provider',
        'model',
        'request_payload',
        'response_payload',
        'response_text',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'status',
        'error_message',
        'latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function statusSuccess(): string
    {
        return 'success';
    }

    public static function statusError(): string
    {
        return 'error';
    }
}
