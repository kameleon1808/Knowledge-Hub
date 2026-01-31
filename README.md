# Knowledge Hub for Teams

**Knowledge Hub** is an internal Q&A and knowledge-base platform for teams, inspired by Stack Overflow and Laracasts. It combines a full Q&A workflow (questions, answers, voting, reputation, categories, tags, comments, bookmarks, notifications) with real-time updates, optional AI-generated answers, and an AI-powered knowledge base (RAG) over project documents and emails. All documentation is in English.

## What This Project Is

- **Q&A core** — Members ask questions, post answers, vote, accept answers, and earn reputation. Content supports Markdown and image uploads.
- **Taxonomy** — Admins manage categories (hierarchical) and tags; members filter and full-text search questions.
- **Social** — Comments on questions/answers, bookmarks, and in-app notifications when someone answers your question.
- **Real-time** — New answers, votes, and notifications appear live via Laravel Reverb (WebSockets).
- **AI (optional)** — Provider-agnostic LLM layer: generate draft answers on questions; all calls audited. Optional auto-answer on new questions.
- **Knowledge Base (Phase I)** — Projects group documents (PDF/DOCX/TXT) and emails. Upload or add manually; vector embeddings and RAG let you “Ask AI” with contextual answers and citations. Export to Markdown or PDF; activity log for uploads, processing, RAG, and exports.

**Stack:** Laravel 12, Inertia + Vue 3, Tailwind, Docker, PostgreSQL with pgvector. Phases A–I are implemented (see below).

## Documentation (`/docs`)

Each phase is documented in a single file (spec, user test plan, and dev notes merged). Reference doc for migrations and assumptions:

| Phase | File | Content |
|-------|------|---------|
| A | `phase-a-initial-setup.md` | Foundation: Laravel 12, Inertia + Vue 3 + Tailwind, Docker, Pint, test skeleton, README |
| B | `phase-b-auth-rbac.md` | Auth, RBAC (Admin/Moderator/Member), admin skeleton, user management |
| C | `phase-c-qa-core.md` | Q&A CRUD, Markdown, image uploads |
| D | `phase-d-voting-accepted-reputation.md` | Voting, accepted answer, reputation |
| E | `phase-e-categories-tags-filters-search.md` | Categories, tags, filters, full-text search |
| F | `phase-f-comments-bookmarks-notifications.md` | Comments, bookmarks, in-app notifications |
| G | `phase-g-realtime-reverb.md` | Real-time (Reverb): answers, votes, notifications |
| H | `phase-h-ai-integration.md` | AI layer (OpenAI/Anthropic/Gemini), Generate AI Answer, audit |
| I | `phase-i-rag-knowledge-base.md` | Projects, documents/emails, RAG, export, activity log |
| — | `migrations-and-seeding.md` | Migration order, seeding, known assumptions |

## Quick Start (First Time)

1. **Prerequisites:** Docker (with Compose), Git. Ensure ports `8080`, `5173`, and `5432` are free.
2. **Configure:** Copy `backend/.env.example` to `backend/.env`. Set `APP_URL=http://localhost:8080` (and optionally AI/Reverb vars; see [Environment variables](#environment-variables)).
3. **Run:** From the repo root: `docker compose up -d --build`, then inside the app container run `composer install`, `php artisan key:generate`, `php artisan migrate`, `php artisan db:seed`, and `php artisan storage:link`.
4. **Optional:** Start a queue worker for Phase I document processing: `docker compose exec app php artisan queue:work`. For real-time (Phase G), start Reverb: `docker compose exec app php artisan reverb:start`.
5. **Open:** http://localhost:8080 — log in with seeded users (e.g. `admin@knowledge-hub.test` / `password`).

Details and Windows/PowerShell equivalents are below.

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

# 5) Generate app key, run migrations, and seed
make artisan CMD="key:generate"
make artisan CMD="migrate"
make artisan CMD="db:seed"
make artisan CMD="storage:link"

# 6) (Optional) Run queue worker for knowledge processing and Reverb for real-time
# In separate terminals:
make queue
# and optionally:
make reverb
```
On Windows PowerShell (without `make`), use:
```powershell
Copy-Item backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
# Queue worker (for Phase I document processing):
docker compose exec app php artisan queue:work
# Reverb (optional, for Phase G real-time):
docker compose exec app php artisan reverb:start
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

## Environment variables

Edit `backend/.env` as needed. Key variables:

- **App:** `APP_URL` (e.g. `http://localhost:8080`)
- **Database:** `DB_*` — `DB_PASSWORD` must match `POSTGRES_PASSWORD` in `docker-compose.yml`
- **Queue:** `QUEUE_CONNECTION` — use `database` (default) or `redis`; Phase I document processing runs via queue jobs
- **Reverb (Phase G):** `REVERB_*` for real-time broadcasting
- **Vite (Docker):** `VITE_DEV_SERVER_URL=http://localhost:5173` — ensures the Vite hot file uses a URL the browser can load (avoids `ERR_ADDRESS_INVALID` / blank page). Set in `docker-compose` for `app` and `node`; keep in `.env` if you run Laravel outside Docker.
- **AI (Phase H / Phase I):** `AI_PROVIDER` (`openai`, `anthropic`, `gemini`, or `mock`), `AI_ENABLED`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`; for RAG embeddings add `AI_EMBEDDING_MODEL` and `AI_EMBEDDING_DIMENSION` (e.g. 1536 for OpenAI text-embedding-3-small). See `docs/phase-h-ai-integration.md` and `docs/phase-i-rag-knowledge-base.md`.

All AI calls (including RAG embeddings and chat) are audited in `ai_audit_logs`.

## Database

Migrations run in filename order. Phase I adds projects, knowledge_items, knowledge_chunks (with pgvector), rag_queries, and activity_logs. See `docs/migrations-and-seeding.md` for full order and reseed instructions.

```bash
make artisan CMD="migrate"
make artisan CMD="db:seed"
# Or reset and reseed:
make artisan CMD="migrate:fresh"
make artisan CMD="db:seed"
make artisan CMD="storage:link"
```

## First-run checklist (zero surprises)
1) Docker is running and ports `8080`, `5173`, and `5432` are free.
2) `backend/.env` exists and `APP_URL=http://localhost:8080`.
3) `docker compose up -d --build` has started `app`, `web`, `db`, and `node`.
4) `composer install`, `php artisan key:generate`, `php artisan migrate`, and `php artisan db:seed` completed successfully; run `php artisan storage:link` for Phase I exports.
5) For Phase I document processing, run a queue worker: `php artisan queue:work` (or `make queue`).
6) Open http://localhost:8080 and you should see the Home page; log in and visit Projects for Phase I RAG.

## Vite hot file fail-safe

After deploy or larger changes, a leftover `public/hot` file (from the Vite dev server) can make the app try to use the dev server and break. You no longer need to remove it manually.

- **Production / staging / non-local:** `public/hot` is removed automatically on container start (Docker entrypoint) and after `composer install` / `composer update`.
- **Local:** `public/hot` is left as-is so Vite works normally. To clear it on boot locally as well, set in `.env`:
  ```env
  CLEAR_VITE_HOT_ON_BOOT=true
  ```

The Artisan command `php artisan app:clear-vite-hot` runs the same logic (skips in local unless `CLEAR_VITE_HOT_ON_BOOT=true`). You can run it manually anytime.

## Troubleshooting
- `make` not found on Windows: use the PowerShell commands above or run in WSL.
- `No application encryption key`: run `php artisan key:generate` inside the app container.
- `Vite manifest not found` or blank page: ensure the `node` service is running or run `npm run build` in that container. If the console shows `ERR_ADDRESS_INVALID` for `client` or `app.js`, the Vite hot file contained an invalid URL (e.g. `[::]` or `0.0.0.0`); the app now fixes this automatically when `VITE_DEV_SERVER_URL` is set (e.g. in `docker-compose`). Restart the `node` and `app` containers so the updated hot file or fallback is used.
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

### Fix for `env: 'bash\r': No such file or directory` (app / queue / reverb)
The entrypoint script had Windows line endings (CRLF). Shell scripts in `docker/` use LF (see `.gitattributes`). Rebuild the image so the fixed script is copied: `docker compose build --no-cache app` then `docker compose up -d`. If you edit `docker/entrypoint.sh` on Windows, save with LF line endings or run `git add --renormalize docker/entrypoint.sh` after changing `.gitattributes`.

### Fix for `host not found in upstream "app"`
Nginx starts before PHP-FPM or the `app` container failed (e.g. due to the entrypoint error above).

### Fix for 502 Bad Gateway
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

## Phase I: RAG Knowledge Base

- **Projects** — Group documents and emails; members can view/search and ask questions; owners can manage settings and members.
- **Knowledge ingestion** — Upload PDF/DOCX/TXT (stored privately, processed via queue); add emails manually (subject, from, body).
- **RAG** — Ask AI tab: question is embedded, vector search returns relevant chunks, LLM answers from context with citations. All embedding and chat calls are audited in `ai_audit_logs`.
- **Export** — Exports tab: download project knowledge as Markdown or PDF.
- **Activity** — Activity tab: recent events (uploads, processing, RAG asks, exports).

Ensure a queue worker is running for document processing (`php artisan queue:work`). AI and embedding configuration: see `docs/phase-i-rag-knowledge-base.md`.

## Architectural decisions

- PostgreSQL + pgvector: used for vector embeddings and similarity search in Phase I RAG.
- Inertia + Vue 3: SPA-like UI with Laravel routing and server-side conventions.
- AI layer (Phase H) is provider-agnostic; all AI usage (including RAG) is logged in `ai_audit_logs`.
