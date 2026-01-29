<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'votable_type' => ['required', 'string', Rule::in(['question', 'answer'])],
            'votable_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
