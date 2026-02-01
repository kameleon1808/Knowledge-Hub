# Production Performance Ops

## Recommended Commands (Run During Deploy)

```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Rebuild caches after any config/route/view changes.

## OPcache

- Enable OPcache with enough memory for the codebase.
- Recommended settings (adjust to environment):
  - `opcache.enable=1`
  - `opcache.memory_consumption=256`
  - `opcache.interned_strings_buffer=16`
  - `opcache.max_accelerated_files=20000`
  - `opcache.validate_timestamps=0` (if deploys clear cache)

## Queue Workers

- Ensure a queue worker is always running for broadcasts and background jobs.
- For Docker dev, the `queue` service already runs `php artisan queue:work`.
- For production, use Supervisor or systemd and set `QUEUE_CONNECTION=database` (or Redis).

## Reverb (Realtime)

- Keep the Reverb server running and reachable by the app and browser.
- Monitor queue latency: broadcasts are queued and should not block requests.
- Ensure `BROADCAST_CONNECTION=reverb` and `VITE_REVERB_*` match your host/port.

## Environment Guidance

- `APP_DEBUG=false` in production.
- Avoid `sync` queue connection in production.
- Confirm DB indexes from `2026_02_01_000000_add_performance_indexes.php` are applied.
