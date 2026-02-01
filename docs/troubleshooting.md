# Troubleshooting and Fixes

This document collects common fixes and recovery steps that were previously in the README.

## Vite hot file fail-safe

After deploy or larger changes, a leftover `public/hot` file (from the Vite dev server) can make the app try to use the dev server and break. You no longer need to remove it manually.

- **Production / staging / non-local:** `public/hot` is removed automatically on container start (Docker entrypoint) and after `composer install` / `composer update`.
- **Local:** `public/hot` is left as-is so Vite works normally. To clear it on boot locally as well, set in `.env`:
  ```env
  CLEAR_VITE_HOT_ON_BOOT=true
  ```

The Artisan command `php artisan app:clear-vite-hot` runs the same logic (skips in local unless `CLEAR_VITE_HOT_ON_BOOT=true`). You can run it manually anytime.

## Common issues

- `No application encryption key`: run `docker compose exec app php artisan key:generate`.
- `Vite manifest not found` or blank page: ensure the `node` service is running or run `docker compose exec node npm run build`. If the console shows `ERR_ADDRESS_INVALID` for `client` or `app.js`, the Vite hot file contained an invalid URL (e.g. `[::]` or `0.0.0.0`); the app now fixes this automatically when `VITE_DEV_SERVER_URL` is set (e.g. in `docker-compose`). Restart the `node` and `app` containers so the updated hot file or fallback is used.
- `npm ENOTEMPTY` (node_modules volume stuck): remove only the `node_modules` volume and restart.
- `host not found in upstream "app"`: the PHP container isn't running; restart `app` first, then `web`.
- Database connection errors: wait ~10 seconds for Postgres to start, then re-run migrations.
- Port conflicts: update ports in `docker-compose.yml` and set `APP_URL` accordingly.

## Fix for `npm ENOTEMPTY` (Windows/first-run safe)

This can happen if `node_modules` was created partially inside the Docker volume. Fix it without touching the database:
```powershell
docker compose down
docker volume ls | findstr node_modules
# remove only the node_modules volume for this project
docker volume rm knowledge-hub_node_modules
docker compose up -d --build
```
If the volume name is different, use the exact name shown by `docker volume ls`.

## Fix for `env: 'bash\r': No such file or directory` (app / queue / reverb)

The entrypoint script had Windows line endings (CRLF). Shell scripts in `docker/` use LF (see `.gitattributes`). Rebuild the image so the fixed script is copied: `docker compose build --no-cache app` then `docker compose up -d`. If you edit `docker/entrypoint.sh` on Windows, save with LF line endings or run `git add --renormalize docker/entrypoint.sh` after changing `.gitattributes`.

## Fix for `host not found in upstream "app"`

Nginx starts before PHP-FPM or the `app` container failed (e.g. due to the entrypoint error above).

## Fix for 502 Bad Gateway

Nginx can reach the app container but PHP-FPM is not responding. The image is set up so PHP-FPM listens on `0.0.0.0:9000` (see `docker/php/zz-docker.conf`). Rebuild the app image: `docker compose build --no-cache app` then `docker compose up -d`. If 502 persists, check `docker compose logs app` for PHP/Laravel errors (e.g. missing `APP_KEY`, database connection).
```powershell
docker compose up -d app db
docker compose logs app --tail=100
docker compose up -d web node
```

## Resetting the environment

Warning: this removes database data.
```bash
docker compose down -v
docker compose up -d --build
```
