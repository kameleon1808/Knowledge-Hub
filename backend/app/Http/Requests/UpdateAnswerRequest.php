<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mimes = implode(',', config('attachments.allowed_mimes', []));
        $maxSize = config('attachments.max_size_kb', 5120);

        return [
            'body_markdown' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', "mimes:{$mimes}", "max:{$maxSize}"],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
        ];
    }
}
