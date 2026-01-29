# Knowledge Hub za Timove

Knowledge Hub za Timove is an internal StackOverflow/Laracasts-inspired platform for teams. Phase A delivers the production-ready foundation: Laravel 12, Inertia + Vue 3 UI, Tailwind styling, and a Dockerized environment with PostgreSQL + pgvector.

## Requirements
- Docker Desktop (or Docker Engine) with Compose
- Git

## Setup (Docker-first)
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

## Running with Docker
- App (Nginx): http://localhost:8080
- Vite dev server: http://localhost:5173
- PostgreSQL: localhost:5432
The Node service runs `npm install` and starts Vite on boot.

Common commands:
```bash
make logs
make artisan CMD="route:list"
make test
make npm CMD="run build"
```

## Environment variables (placeholders)
Edit `backend/.env` as needed. Phase A ships placeholders only.
- `APP_URL`
- `DB_*`
- `REVERB_*`
- `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`

## Database
```bash
make artisan CMD="migrate"
make artisan CMD="db:seed"
```
Baseline role migrations run after the default users table; the pivot depends on both.

## Architectural decisions
- PostgreSQL + pgvector: chosen for future vector search/RAG capabilities.
- Inertia + Vue 3: keeps a modern SPA-like UI without abandoning Laravel routing or server-side conventions.

## Known limitations (Phase A)
- No business features yet (no Q&A, voting, or AI logic).
- No authentication or authorization policies.
- Minimal UI, focused only on proving the stack end-to-end.
