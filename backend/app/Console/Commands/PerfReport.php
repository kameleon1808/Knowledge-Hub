<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PerfReport extends Command
{
    protected $signature = 'perf:report {--limit=5 : Number of routes to show} {--lines=1000 : Recent log lines to scan}';

    protected $description = 'Report slowest routes from recent perf logs.';

    public function handle(): int
    {
        $path = storage_path('app/perf_logs.jsonl');
        if (! is_file($path)) {
            $this->warn('No perf log file found. Enable PERF_LOG=true and hit some routes.');
            return self::SUCCESS;
        }

        $lines = max(1, (int) $this->option('lines'));
        $limit = max(1, (int) $this->option('limit'));

        $raw = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($raw) || $raw === []) {
            $this->warn('Perf log file is empty.');
            return self::SUCCESS;
        }

        $recent = array_slice($raw, max(0, count($raw) - $lines));
        $stats = [];

        foreach ($recent as $line) {
            $row = json_decode($line, true);
            if (! is_array($row)) {
                continue;
            }

            $route = $row['route'] ?? 'unknown';
            $method = $row['method'] ?? 'GET';
            $key = "{$method} {$route}";

            if (! isset($stats[$key])) {
                $stats[$key] = [
                    'route' => $key,
                    'count' => 0,
                    'total_ms' => 0.0,
                    'max_ms' => 0.0,
                    'total_queries' => 0,
                    'total_query_ms' => 0.0,
                ];
            }

            $duration = (float) ($row['duration_ms'] ?? 0);
            $queryCount = (int) ($row['query_count'] ?? 0);
            $queryTime = (float) ($row['query_time_ms'] ?? 0);

            $stats[$key]['count']++;
            $stats[$key]['total_ms'] += $duration;
            $stats[$key]['max_ms'] = max($stats[$key]['max_ms'], $duration);
            $stats[$key]['total_queries'] += $queryCount;
            $stats[$key]['total_query_ms'] += $queryTime;
        }

        $rows = array_values(array_map(function (array $row): array {
            $count = max(1, $row['count']);
            return [
                'Route' => $row['route'],
                'Requests' => $row['count'],
                'Avg ms' => round($row['total_ms'] / $count, 2),
                'Max ms' => round($row['max_ms'], 2),
                'Avg queries' => round($row['total_queries'] / $count, 1),
                'Avg query ms' => round($row['total_query_ms'] / $count, 2),
            ];
        }, $stats));

        usort($rows, fn ($a, $b) => $b['Avg ms'] <=> $a['Avg ms']);

        $this->table(
            ['Route', 'Requests', 'Avg ms', 'Max ms', 'Avg queries', 'Avg query ms'],
            array_slice($rows, 0, $limit)
        );

        return self::SUCCESS;
    }
}
