# Migrations, Seeding, and Known Assumptions

## Known Assumptions (Project-Wide)

- **Phase B:** Roles are stored as a validated string column on `users` (no roles pivot). Admin access for `/admin/*`; moderator for `/moderator/*`. Admins do not auto-access moderator area. User deletion not implemented; only role updates. Demo credentials use password `password` (local dev only).
- **Phases C–I:** See each phase doc for data-model and authorization assumptions.

## Migration Order

Migrations run in filename order. Phase I migrations are dated `2026_01_30_200*` and run after Phase H (`2026_01_30_100*`).

1. **Base** — users, cache, jobs, roles, role_user, questions, answers, attachments, votes, accepted_answer, reputation, reputation_events, categories, tags, search_vectors, comments, bookmarks, notifications.
2. **Phase H** — ai_audit_logs, is_system on users, ai fields on answers.
3. **Phase I** — projects, project_user, knowledge_items, knowledge_chunks (with vector or text column), rag_queries, activity_logs.

## What Each Phase I Migration Does

- **2026_01_30_200000_create_projects_table** — Creates `projects` (name, description, owner_user_id).
- **2026_01_30_200001_create_project_user_table** — Creates `project_user` pivot (project_id, user_id, role) with unique(project_id, user_id).
- **2026_01_30_200002_create_knowledge_items_table** — Creates `knowledge_items` (project_id, type, title, source_meta, original_content_path, raw_text, status, error_message).
- **2026_01_30_200003_create_knowledge_chunks_table** — Creates `knowledge_chunks` (knowledge_item_id, chunk_index, content_text, content_hash, tokens_count). On PostgreSQL adds `embedding vector(1536)` and HNSW index; on SQLite adds text `embedding` for tests.
- **2026_01_30_200004_create_rag_queries_table** — Creates `rag_queries` (project_id, user_id, question_text, answer_text, cited_chunk_ids, provider, model).
- **2026_01_30_200005_create_activity_logs_table** — Creates `activity_logs` (actor_user_id, action, subject_type, subject_id, project_id, metadata, created_at).

## Resetting the Database and Reseeding

From the project root (Docker):

```bash
docker compose exec app php artisan migrate:fresh --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
```

From `backend/` (local):

```bash
php artisan migrate:fresh --force
php artisan db:seed --force
php artisan storage:link
```

`migrate:fresh` drops all tables and re-runs all migrations. `db:seed` runs `DatabaseSeeder`, which creates users (admin, moderator, member, AI assistant), categories, tags, questions, answers, votes, comments, bookmarks, and (if added) Phase I demo data.

## Phase I Seed Data

The Phase I seed (in `DatabaseSeeder` or a dedicated seeder) can create:

- A sample project owned by the admin user.
- One or more knowledge items (e.g. one TXT and one email) with status `processed` and sample chunks (optional), or raw items for the user to process.
- Sample RAG query records for the project.

See `database/seeders/` for the actual seeder implementation.
