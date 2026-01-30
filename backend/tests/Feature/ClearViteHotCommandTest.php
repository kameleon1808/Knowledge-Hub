<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ClearViteHotCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $hotPath = public_path('hot');
        if (File::exists($hotPath)) {
            File::delete($hotPath);
        }
        parent::tearDown();
    }

    public function test_removes_hot_file_when_not_local(): void
    {
        $this->app['config']->set('app.env', 'production');

        $hotPath = public_path('hot');
        File::put($hotPath, 'http://localhost:5173');

        $this->assertFileExists($hotPath);

        $exitCode = Artisan::call('app:clear-vite-hot');

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($hotPath);
    }

    public function test_nothing_to_do_when_hot_file_missing(): void
    {
        $this->app['config']->set('app.env', 'production');

        $hotPath = public_path('hot');
        $this->assertFileDoesNotExist($hotPath);

        $exitCode = Artisan::call('app:clear-vite-hot');

        $this->assertSame(0, $exitCode);
    }

    public function test_removes_hot_file_in_local_when_override_set(): void
    {
        $this->app['config']->set('app.env', 'local');
        putenv('CLEAR_VITE_HOT_ON_BOOT=true');

        $hotPath = public_path('hot');
        File::put($hotPath, 'http://localhost:5173');

        $exitCode = Artisan::call('app:clear-vite-hot');

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($hotPath);

        putenv('CLEAR_VITE_HOT_ON_BOOT');
    }
}
