<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Bookmark;
use App\Models\User;
use App\Models\Question;
use App\Policies\AnswerPolicy;
use App\Policies\CommentPolicy;
use App\Policies\BookmarkPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Question::class => QuestionPolicy::class,
        Answer::class => AnswerPolicy::class,
        Comment::class => CommentPolicy::class,
        Bookmark::class => BookmarkPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
        Gate::define('moderate-content', fn (User $user) => $user->isAdmin() || $user->isModerator());
        Gate::define('manage-own-content', fn (User $user, int $ownerId) => $user->isAdmin()
            || $user->isModerator()
            || $user->id === $ownerId);
    }
}
