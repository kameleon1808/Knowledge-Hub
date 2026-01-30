<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskRagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('askRag', $project);
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:4000'],
        ];
    }
}
