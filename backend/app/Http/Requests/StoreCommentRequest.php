<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Comment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', Rule::in(['question', 'answer'])],
            'commentable_id' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                $type = $this->string('commentable_type')->toString();
                $model = $type === 'answer' ? Answer::class : Question::class;
                if (!$model::query()->whereKey($value)->exists()) {
                    $fail('Invalid commentable.');
                }
            }],
            'body_markdown' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
