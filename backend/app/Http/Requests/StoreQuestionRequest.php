<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'body_markdown' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', "mimes:{$mimes}", "max:{$maxSize}"],
        ];
    }
}
