<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $body = fake()->paragraphs(2, true);

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body_markdown' => $body,
            'body_html' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
