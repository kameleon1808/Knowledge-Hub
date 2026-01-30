<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearViteHot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-vite-hot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Vite dev server hot file (public/hot) when not in local dev';

    /**
     * Execute the console command.
     * Skips deletion in local environment unless CLEAR_VITE_HOT_ON_BOOT=true.
     */
    public function handle(): int
    {
        if ($this->shouldSkip()) {
            $this->info('Skipped (local environment; set CLEAR_VITE_HOT_ON_BOOT=true to clear).');
            return self::SUCCESS;
        }

        $hotPath = public_path('hot');

        if (! is_file($hotPath)) {
            $this->info('Nothing to do — public/hot does not exist.');
            return self::SUCCESS;
        }

        if (@unlink($hotPath)) {
            $this->info('Removed public/hot.');
            Log::info('Vite hot file removed.', ['path' => $hotPath]);
            return self::SUCCESS;
        }

        $this->warn('Could not remove public/hot (check permissions).');
        return self::SUCCESS;
    }

    /**
     * Skip clearing in local unless CLEAR_VITE_HOT_ON_BOOT is set.
     */
    protected function shouldSkip(): bool
    {
        if (app()->environment('local')) {
            return filter_var(env('CLEAR_VITE_HOT_ON_BOOT'), FILTER_VALIDATE_BOOLEAN) !== true;
        }

        return false;
    }
}
