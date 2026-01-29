<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\Comment;
use App\Models\Bookmark;
use App\Models\Vote;
use App\Notifications\AnswerPostedOnYourQuestion;
use App\Services\AcceptanceService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Services\MarkdownService;
use App\Services\VoteService;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $markdown = app(MarkdownService::class);
        $voteService = app(VoteService::class);
        $acceptanceService = app(AcceptanceService::class);

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@knowledge-hub.test',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Moderator User',
                'email' => 'moderator@knowledge-hub.test',
                'role' => User::ROLE_MODERATOR,
            ],
            [
                'name' => 'Član User',
                'email' => 'member@knowledge-hub.test',
                'role' => User::ROLE_MEMBER,
            ],
        ];

        $seededUsers = [];

        foreach ($users as $user) {
            $seededUsers[$user['role']] = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $categories = [
            [
                'name' => 'Processes',
                'description' => 'Team processes and playbooks',
                'children' => [
                    ['name' => 'Onboarding', 'description' => 'New joiner guides'],
                    ['name' => 'Deployments', 'description' => 'Release workflows'],
                ],
            ],
            [
                'name' => 'Engineering',
                'description' => 'Engineering practices',
                'children' => [
                    ['name' => 'Testing', 'description' => 'Quality practices'],
                    ['name' => 'Incidents', 'description' => 'Incident response'],
                ],
            ],
            [
                'name' => 'Product',
                'description' => 'Product knowledge',
                'children' => [
                    ['name' => 'Research', 'description' => 'User research'],
                    ['name' => 'Roadmap', 'description' => 'Planning and prioritization'],
                ],
            ],
        ];

        $categoryMap = collect();

        foreach ($categories as $categoryData) {
            $root = Category::updateOrCreate(
                ['slug' => Str::slug($categoryData['name'])],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'parent_id' => null,
                ]
            );

            $categoryMap->push($root);

            foreach ($categoryData['children'] as $childData) {
                $child = Category::updateOrCreate(
                    ['slug' => Str::slug($childData['name'])],
                    [
                        'name' => $childData['name'],
                        'description' => $childData['description'],
                        'parent_id' => $root->id,
                    ]
                );
                $categoryMap->push($child);
            }
        }

        $tagNames = [
            'Documentation',
            'CI/CD',
            'Monitoring',
            'Security',
            'UX',
            'API',
            'Database',
            'Performance',
            'Release',
            'Testing',
            'Analytics',
            'Infrastructure',
            'Productivity',
            'Retrospective',
            'Postmortem',
            'Runbook',
            'SLA',
            'Backup',
            'Design System',
            'Accessibility',
        ];

        $tagIds = collect($tagNames)->map(function ($name) {
            $tag = Tag::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            return $tag->id;
        });

        $questionTemplates = [
            User::ROLE_ADMIN => [
                'How do we structure team knowledge updates?',
                'What should go into onboarding playbooks?',
            ],
            User::ROLE_MODERATOR => [
                'Best practices for documenting deployments?',
                'How to standardize meeting notes?',
            ],
            User::ROLE_MEMBER => [
                'Where do we track project retrospectives?',
                'How to share learnings from incidents?',
            ],
        ];

        $questionRecords = [];

        foreach ($questionTemplates as $role => $titles) {
            $author = $seededUsers[$role];

            foreach ($titles as $title) {
                $body = "- Context: Share what you already tried.\n- Expected outcome: Describe the goal.\n- Additional details welcome.";

                $questionRecords[] = Question::updateOrCreate(
                    ['user_id' => $author->id, 'title' => $title],
                    [
                        'body_markdown' => $body,
                        'body_html' => $markdown->toHtml($body),
                    ]
                );
            }
        }

        foreach ($questionRecords as $question) {
            $category = $categoryMap->random();
            $question->update(['category_id' => $category->id]);
            $question->tags()->sync($tagIds->shuffle()->take(rand(2, 5)));

            $responders = collect($seededUsers)->reject(fn ($user) => $user->id === $question->user_id);

            foreach ($responders as $responder) {
                $body = "Here is a suggested structure:\n\n1. Capture the summary.\n2. Link relevant resources.\n3. Assign next steps.";

                Answer::updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'user_id' => $responder->id,
                    ],
                    [
                        'body_markdown' => $body,
                        'body_html' => $markdown->toHtml($body),
                    ]
                );
            }
        }

        $admin = $seededUsers[User::ROLE_ADMIN];
        $moderator = $seededUsers[User::ROLE_MODERATOR];
        $member = $seededUsers[User::ROLE_MEMBER];

        foreach ($questionRecords as $question) {
            $question->load(['author', 'answers']);

            $questionVoter = $question->user_id === $admin->id ? $moderator : $admin;
            $this->ensureVote($voteService, $questionVoter, $question, 1);

            $acceptedAnswer = $question->answers->first();
            if ($acceptedAnswer && $question->accepted_answer_id !== $acceptedAnswer->id) {
                $acceptanceService->acceptAnswer($question, $acceptedAnswer, $question->author);
            }

            foreach ($question->answers as $index => $answer) {
                $answerVoter = $answer->user_id === $member->id ? $moderator : $member;
                $value = $index % 2 === 0 ? 1 : -1;
                $this->ensureVote($voteService, $answerVoter, $answer, $value);
            }

            $commentAuthor = $question->author->is($admin) ? $moderator : $admin;
            Comment::updateOrCreate(
                [
                    'user_id' => $commentAuthor->id,
                    'commentable_type' => Question::class,
                    'commentable_id' => $question->id,
                ],
                [
                    'body_markdown' => 'Thanks for sharing this question. Adding a short comment for context.',
                    'body_html' => $markdown->toHtml('Thanks for sharing this question. Adding a short comment for context.'),
                ]
            );

            foreach ($question->answers as $answer) {
                Comment::updateOrCreate(
                    [
                        'user_id' => $member->id,
                        'commentable_type' => Answer::class,
                        'commentable_id' => $answer->id,
                    ],
                    [
                        'body_markdown' => 'Quick note: ensure the steps include owners.',
                        'body_html' => $markdown->toHtml('Quick note: ensure the steps include owners.'),
                    ]
                );

                if ($answer->user_id !== $question->user_id) {
                    $existingNotification = $question->author?->notifications()
                        ->where('data->answer_id', (string) $answer->id)
                        ->exists();

                    if (!$existingNotification && $question->author) {
                        $question->author->notify(new AnswerPostedOnYourQuestion($answer));
                    }
                }
            }
        }

        $allQuestions = Question::all();
        foreach ($seededUsers as $user) {
            $allQuestions->shuffle()->take(2)->each(function ($question) use ($user) {
                Bookmark::firstOrCreate([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                ]);
            });
        }
    }

    private function ensureVote(VoteService $voteService, User $voter, $votable, int $value): void
    {
        $existing = Vote::query()
            ->where('user_id', $voter->id)
            ->where('votable_type', $votable->getMorphClass())
            ->where('votable_id', $votable->getKey())
            ->first();

        if ($existing && $existing->value === $value) {
            return;
        }

        if ($voter->id === $votable->user_id) {
            return;
        }

        $voteService->castVote($voter, $votable, $value);
    }
}
