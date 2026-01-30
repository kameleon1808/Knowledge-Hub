# Phase A: Initial Setup (Foundation)

## Overview

Phase A establishes the foundation for the Knowledge Hub: Laravel 12 with Inertia (Vue 3) and Tailwind, a complete Docker-based dev environment, a clear module/folder structure, coding standards (Laravel Pint), a basic test skeleton, and a README with setup and environment-variable placeholders. No credentials or secrets are committed to the repository.

**Deliverables:**

1. **Stack** — Laravel 12, Inertia.js, Vue 3, Tailwind CSS.
2. **Docker** — `docker-compose` with app (PHP-FPM), web (Nginx), db (PostgreSQL), and node (Vite); full dev setup so the app runs in containers.
3. **Structure** — Sensible module/folder layout in `backend/` (e.g. `app/`, `config/`, `database/`, `resources/js/`, Inertia pages).
4. **Coding standards** — Laravel Pint configured for consistent PHP formatting.
5. **Tests** — Basic test skeleton (e.g. `tests/Feature`, `tests/Unit`); at least one passing test or placeholder to run `php artisan test`.
6. **README** — Skeleton with setup instructions and env var placeholders (no real API keys or passwords in repo).

## Stack and Layout

- **Backend:** Laravel 12, PHP 8.2+ (or as per Dockerfile).
- **Frontend:** Inertia.js (server-side routing, Vue as driver), Vue 3, Tailwind CSS, Vite.
- **Database:** PostgreSQL (used from Phase B onward; Phase A may use SQLite for minimal setup or already use PostgreSQL in Docker).
- **Containers:** `app` (PHP-FPM + Laravel), `web` (Nginx), `db` (PostgreSQL), `node` (npm install + Vite dev server).

Project root contains `docker-compose.yml`, `Makefile` (optional), and `backend/` with the Laravel application. Frontend assets live under `backend/resources/js` with Inertia page components.

## Docker Dev Setup

- **docker-compose** defines services: `app`, `web`, `db`, `node` (and later `queue`, `reverb` as needed).
- **app:** Runs Laravel; document root and PHP-FPM configured to serve the app; dependencies installed via `composer install` (run once or via entrypoint).
- **web:** Nginx, proxies to `app` for PHP and to `node` for Vite HMR when used.
- **db:** PostgreSQL; credentials and port (e.g. 5432) exposed for local use; no production credentials in repo.
- **node:** Runs `npm install` and `npm run dev` (Vite); port 5173 for dev server.

Ports (examples): 8080 (HTTP app), 5173 (Vite), 5432 (PostgreSQL). All configurable via env and `docker-compose.yml`.

## Coding Standards (Pint)

- Laravel Pint is set up (e.g. `laravel/pint` in dev dependencies, config in `pint.json` or default).
- Running `./vendor/bin/pint` (or `make pint` if present) formats PHP code according to project rules.
- No credentials or environment-specific values are stored in the repository; `.env` is gitignored and `.env.example` contains only placeholders.

## Test Skeleton

- **Feature tests:** e.g. `tests/Feature/` for HTTP/Inertia flows (optional placeholder or a single home-page test).
- **Unit tests:** e.g. `tests/Unit/` for non-HTTP logic (optional placeholder).
- `php artisan test` runs the suite and passes (at least one test or an empty suite that exits 0).

## README and Env Placeholders

- **README** (repo root or linked) describes:
  - What the project is (skeleton).
  - How to run it the first time (clone, copy `.env.example` to `.env`, Docker commands, `composer install`, `php artisan key:generate`, etc.).
  - Which environment variables are needed, with placeholders only (e.g. `APP_KEY=`, `DB_*`, `APP_URL=`); no real keys or passwords.
- **.env.example** lists all required variables with safe placeholder or empty values; no credentials committed.

## Security Notes

- No API keys, passwords, or secrets are committed. `.env` is in `.gitignore`.
- `.env.example` and README guide developers to set their own values locally.

---

## Verification Checklist (Phase A Complete)

Use this to confirm Phase A is in place before moving to Phase B.

1. **Stack**
   - Laravel 12 app in `backend/` runs with Inertia and Vue 3; Tailwind is used for styling; Vite is the build tool.

2. **Docker**
   - `docker compose up -d --build` starts `app`, `web`, `db`, and `node`.
   - App is reachable at http://localhost:8080 (or configured port); Vite dev server at 5173 if needed.
   - No hardcoded credentials in `docker-compose.yml`; secrets come from env or `.env`.

3. **Structure**
   - Clear separation: Laravel in `backend/`, Docker config in `docker/` or root; Inertia pages and Vue components under `backend/resources/js/Pages` (or equivalent).

4. **Pint**
   - `./vendor/bin/pint` (or project equivalent) runs and formats code; config present if custom rules are used.

5. **Tests**
   - `php artisan test` executes (from host or inside `app` container); at least one test or a passing skeleton.

6. **README and env**
   - README explains first-time setup and lists required env vars.
   - `.env.example` exists with placeholders only; no real credentials in the repo.
