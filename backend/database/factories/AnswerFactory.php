<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Answer>
 */
class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        $body = fake()->paragraphs(2, true);

        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory(),
            'body_markdown' => $body,
            'body_html' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
