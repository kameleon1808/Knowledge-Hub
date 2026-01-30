# Knowledge Hub za Timove

Knowledge Hub za Timove is an internal StackOverflow/Laracasts-inspired platform for teams. Phases A–H are complete; Phase I adds an AI Knowledge Base (RAG), export (Markdown/PDF), activity log, and final polish. Stack: Laravel 12, Inertia + Vue 3, Tailwind, Docker, PostgreSQL + pgvector.

Documentation in `/docs` (English):
- **Phase B** — Auth/RBAC: `phase-b-auth-rbac.md`, `user-test-plan-phase-b.md`
- **Phase C** — Q&A core: `phase-c-qa-core.md`, `user-test-plan-phase-c.md`, `dev-notes-phase-c.md`
- **Phase D** — Voting/accepted/reputation: `phase-d-voting-accepted-reputation.md`, `user-test-plan-phase-d.md`, `dev-notes-phase-d.md`
- **Phase E** — Categories/tags/filters/search: `phase-e-categories-tags-filters-search.md`, `user-test-plan-phase-e.md`, `dev-notes-phase-e.md`
- **Phase F** — Comments/bookmarks/notifications: `phase-f-comments-bookmarks-notifications.md`, `user-test-plan-phase-f.md`, `dev-notes-phase-f.md`
- **Phase G** — Real-time (Reverb): `phase-g-realtime-reverb.md`, `user-test-plan-phase-g.md`, `dev-notes-phase-g.md`
- **Phase H** — AI integration (provider-agnostic, audit): `phase-h-ai-integration.md`, `user-test-plan-phase-h.md`, `dev-notes-phase-h.md`
- **Phase I** — RAG Knowledge Base, Export, Activity log: `phase-i-rag-knowledge-base.md`, `user-test-plan-phase-i.md`, `dev-notes-phase-i.md`, `migrations-and-seeding.md`

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
- **AI (Phase H / Phase I):** `AI_DRIVER` (`openai`, `anthropic`, `gemini`, or `mock`), `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`; for RAG embeddings add `AI_EMBEDDING_MODEL` and `AI_EMBEDDING_DIMENSION` (e.g. 1536 for OpenAI text-embedding-3-small). See `docs/phase-i-rag-knowledge-base.md` and `docs/phase-h-ai-integration.md`.

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
