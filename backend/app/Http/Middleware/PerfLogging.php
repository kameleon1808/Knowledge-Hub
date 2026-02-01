<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class PerfLogging
{
    private const LOG_FILENAME = 'perf_logs.jsonl';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog()) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;

        DB::listen(function ($query) use (&$queryCount, &$queryTimeMs): void {
            $queryCount++;
            $queryTimeMs += (float) $query->time;
        });

        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->writeEntry([
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => $request->route()?->getName() ?? $request->path(),
            'duration_ms' => round($durationMs, 2),
            'query_count' => $queryCount,
            'query_time_ms' => round($queryTimeMs, 2),
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }

    private function shouldLog(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        return filter_var(env('PERF_LOG', false), FILTER_VALIDATE_BOOLEAN) === true;
    }

    private function writeEntry(array $entry): void
    {
        $path = storage_path('app/'.self::LOG_FILENAME);
        File::ensureDirectoryExists(dirname($path));

        file_put_contents($path, json_encode($entry).PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
