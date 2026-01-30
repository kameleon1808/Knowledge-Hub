<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('addKnowledge', $project);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'from' => ['nullable', 'string', 'max:255'],
            'sent_at' => ['nullable', 'date'],
            'body_text' => ['required', 'string', 'max:100000'],
        ];
    }
}
