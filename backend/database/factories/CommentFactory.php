<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $body = fake()->paragraph();

        return [
            'user_id' => User::factory(),
            'commentable_type' => Question::class,
            'commentable_id' => Question::factory(),
            'body_markdown' => $body,
            'body_html' => '<p>'.$body.'</p>',
        ];
    }

    public function forAnswer(?Answer $answer = null): self
    {
        return $this->state(function () use ($answer) {
            $answer ??= Answer::factory()->create();

            return [
                'commentable_type' => Answer::class,
                'commentable_id' => $answer,
            ];
        });
    }
}
