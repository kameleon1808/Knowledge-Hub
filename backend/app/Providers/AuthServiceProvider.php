<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
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
