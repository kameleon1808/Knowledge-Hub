<?php

return [

    'enabled' => env('AI_ENABLED', false),

    'provider' => env('AI_PROVIDER', 'openai'),

    'auto_answer' => env('AI_AUTO_ANSWER', false),

    'model' => env('AI_MODEL'),

    'timeout' => (int) env('AI_TIMEOUT_SECONDS', 30),

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 700),

    'temperature' => (float) env('AI_TEMPERATURE', 0.3),

    'providers' => [
        'mock' => [
            'key' => null,
            'default_model' => 'mock',
        ],
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'default_model' => 'gpt-4o-mini',
        ],
        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'default_model' => 'claude-3-5-haiku-20241022',
        ],
        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'default_model' => 'gemini-2.5-flash',
        ],
    ],

];
