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
| — | `troubleshooting.md` | Common fixes and recovery steps |

## Quick Start (First Time)

1. **Prerequisites:** Docker (with Compose), Git. Ensure ports `8080`, `5173`, and `5432` are free.
2. **Configure:** Copy `backend/.env.example` to `backend/.env`. Set `APP_URL=http://localhost:8080` (and optionally AI/Reverb vars; see [Environment variables](#environment-variables)).
3. **Run:** Build and start services, then bootstrap the app.
4. **Optional:** Start a queue worker for Phase I document processing and Reverb for real-time.
5. **Open:** http://localhost:8080 — log in with seeded users (e.g. `admin@knowledge-hub.test` / `password`).

### Commands (bash)
```bash
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
```

### Commands (PowerShell)
```powershell
Copy-Item backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
```

Optional workers (run in separate terminals):
```bash
docker compose exec app php artisan queue:work
docker compose exec app php artisan reverb:start
```

## Requirements
- Docker Desktop (or Docker Engine) with Compose
- Git

## Running with Docker
- App (Nginx): http://localhost:8080
- Vite dev server: http://localhost:5173
- PostgreSQL: localhost:5432

Common commands:
```bash
docker compose logs -f --tail=100
docker compose exec app php artisan route:list
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose down
```

## Environment variables

Edit `backend/.env` as needed. Key variables:

- **App:** `APP_URL` (e.g. `http://localhost:8080`)
- **Database:** `DB_*` — `DB_PASSWORD` must match `POSTGRES_PASSWORD` in `docker-compose.yml`
- **Queue:** `QUEUE_CONNECTION` — use `database` (default) or `redis`; Phase I document processing runs via queue jobs
- **Reverb (Phase G):** `REVERB_*` for real-time broadcasting
- **AI (Phase H / Phase I):** `AI_PROVIDER` (`openai`, `anthropic`, `gemini`, or `mock`), `AI_ENABLED`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`; for RAG embeddings add `AI_EMBEDDING_MODEL` and `AI_EMBEDDING_DIMENSION` (e.g. 1536 for OpenAI text-embedding-3-small). See `docs/phase-h-ai-integration.md` and `docs/phase-i-rag-knowledge-base.md`.

All AI calls (including RAG embeddings and chat) are audited in `ai_audit_logs`.

## Database

Migrations run in filename order. Phase I adds projects, knowledge_items, knowledge_chunks (with pgvector), rag_queries, and activity_logs. See `docs/migrations-and-seeding.md` for full order and reseed instructions.

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## Troubleshooting
For common fixes and recovery steps, see `docs/troubleshooting.md`.

## Architectural decisions

- PostgreSQL + pgvector: used for vector embeddings and similarity search in Phase I RAG.
- Inertia + Vue 3: SPA-like UI with Laravel routing and server-side conventions.
- AI layer (Phase H) is provider-agnostic; all AI usage (including RAG) is logged in `ai_audit_logs`.
