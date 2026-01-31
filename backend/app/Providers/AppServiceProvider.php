<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Answer;
use App\Models\Question;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureViteHotUrlIsBrowserUsable();

        Vite::prefetch(concurrency: 3);

        Relation::morphMap([
            'question' => Question::class,
            'answer' => Answer::class,
        ]);
    }

    /**
     * When running in Docker (or when Vite binds to 0.0.0.0/[::]), public/hot
     * can contain a URL the browser cannot use (ERR_ADDRESS_INVALID).
     * If so, use VITE_DEV_SERVER_URL so script tags point to a valid host (e.g. localhost).
     */
    private function ensureViteHotUrlIsBrowserUsable(): void
    {
        $hotPath = public_path('hot');
        if (! is_file($hotPath)) {
            return;
        }

        $content = trim((string) file_get_contents($hotPath));
        if ($content === '') {
            return;
        }

        $isUnusableInBrowser = str_contains($content, '[::]')
            || str_contains($content, '0.0.0.0');

        if (! $isUnusableInBrowser) {
            return;
        }

        $override = config('app.vite_dev_server_url') ?? env('VITE_DEV_SERVER_URL');
        if ($override === null || $override === '') {
            return;
        }

        $override = rtrim($override, '/');
        $customHotPath = storage_path('app/vite-hot-url');
        if (! is_dir(dirname($customHotPath))) {
            mkdir(dirname($customHotPath), 0755, true);
        }
        file_put_contents($customHotPath, $override);
        Vite::useHotFile($customHotPath);
    }
}
