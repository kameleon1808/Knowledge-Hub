<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('addKnowledge', $project);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:51200'], // 50MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a PDF, DOCX, or TXT file.',
            'file.mimes' => 'Only PDF, DOCX, and TXT files are allowed.',
        ];
    }
}
