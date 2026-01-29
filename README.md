# Knowledge Hub za Timove

Knowledge Hub za Timove is an internal StackOverflow/Laracasts-inspired platform for teams. Phase A delivers the production-ready foundation: Laravel 12, Inertia + Vue 3 UI, Tailwind styling, and a Dockerized environment with PostgreSQL + pgvector.

Phase B authentication/RBAC details and demo credentials are documented in `/docs`.
Phase C Q&A core documentation and test plan:
- `/docs/phase-c-qa-core.md`
- `/docs/user-test-plan-phase-c.md`
- `/docs/dev-notes-phase-c.md`
Phase D voting/accepted/reputation documentation and test plan:
- `/docs/phase-d-voting-accepted-reputation.md`
- `/docs/user-test-plan-phase-d.md`
- `/docs/dev-notes-phase-d.md`
Phase E categories/tags/filters/search documentation and test plan:
- `/docs/phase-e-categories-tags-filters-search.md` (with `/docs/user-test-plan-phase-e.md` and `/docs/dev-notes-phase-e.md`)
Phase F comments/bookmarks/notifications documentation and test plan:
- `/docs/phase-f-comments-bookmarks-notifications.md`
- `/docs/user-test-plan-phase-f.md`
- `/docs/dev-notes-phase-f.md`

## Requirements
- Docker Desktop (or Docker Engine) with Compose
- Git
- (Windows) PowerShell or WSL

## Setup (Docker-first)
Project structure: the Laravel app lives in `backend/`.

```bash
# 1) Clone and enter the repo
# 2) Copy environment file
cp backend/.env.example backend/.env

# 3) Build and start services
make up

# 4) Install PHP dependencies
make composer CMD="install"

# 5) Generate app key and run migrations
make artisan CMD="key:generate"
make artisan CMD="migrate"
```
On Windows PowerShell (without `make`), use:
```powershell
Copy-Item backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

## Running with Docker
- App (Nginx): http://localhost:8080
- Vite dev server: http://localhost:5173
- PostgreSQL: localhost:5432
The Node service runs `npm install` and starts Vite on boot. If you stop the node service, run a one-time build with `make npm CMD="run build"` (or PowerShell equivalent).

Common commands:
```bash
make logs
make artisan CMD="route:list"
make test
make npm CMD="run build"
```
PowerShell equivalents:
```powershell
docker compose logs -f --tail=100
docker compose exec app php artisan route:list
docker compose exec app php artisan test
docker compose exec node npm run build
```

## Environment variables (placeholders)
Edit `backend/.env` as needed. Phase A ships placeholders only.
- `APP_URL`
- `DB_*`
- `REVERB_*`
- `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`
Important: `DB_PASSWORD` in `backend/.env` must match the `POSTGRES_PASSWORD` value in `docker-compose.yml`.

## Database
```bash
make artisan CMD="migrate"
make artisan CMD="db:seed"
```
Baseline role migrations run after the default users table; the pivot depends on both.

## First-run checklist (zero surprises)
1) Docker is running and ports `8080`, `5173`, and `5432` are free.
2) `backend/.env` exists and `APP_URL=http://localhost:8080`.
3) `docker compose up -d --build` has started `app`, `web`, `db`, and `node`.
4) `composer install`, `php artisan key:generate`, and `php artisan migrate` completed successfully.
5) Open http://localhost:8080 and you should see the Home page.

## Troubleshooting
- `make` not found on Windows: use the PowerShell commands above or run in WSL.
- `No application encryption key`: run `php artisan key:generate` inside the app container.
- `Vite manifest not found` or blank page: ensure the `node` service is running or run `npm run build` in that container.
- `npm ENOTEMPTY` (node_modules volume stuck): remove only the `node_modules` volume and restart.
- `host not found in upstream "app"`: the PHP container isn't running; restart `app` first, then `web`.
- Database connection errors: wait ~10 seconds for Postgres to start, then re-run migrations.
- Port conflicts: update ports in `docker-compose.yml` and set `APP_URL` accordingly.

### Fix for `npm ENOTEMPTY` (Windows/first-run safe)
This can happen if `node_modules` was created partially inside the Docker volume. Fix it without touching the database:
```powershell
docker compose down
docker volume ls | findstr node_modules
# remove only the node_modules volume for this project
docker volume rm knowledge-hub_node_modules
docker compose up -d --build
```
If the volume name is different, use the exact name shown by `docker volume ls`.

### Fix for `host not found in upstream "app"`
Nginx starts before PHP-FPM or the `app` container failed.
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

## Architectural decisions
- PostgreSQL + pgvector: chosen for future vector search/RAG capabilities.
- Inertia + Vue 3: keeps a modern SPA-like UI without abandoning Laravel routing or server-side conventions.

## Known limitations (Phase A)
- No business features yet (no Q&A, voting, or AI logic).
- No authentication or authorization policies.
- Minimal UI, focused only on proving the stack end-to-end.
