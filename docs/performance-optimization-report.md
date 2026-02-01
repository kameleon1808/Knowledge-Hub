# Performance Optimization Report

## Baseline (Phase 1)

Run with `PERF_LOG=true` and collect recent samples:

```
php artisan perf:report --limit=5 --lines=1000
```

Top 5 slow routes (populate after running the command above):

| Route | Requests | Avg ms | Max ms | Avg queries | Avg query ms |
| --- | --- | --- | --- | --- | --- |
| TBD | TBD | TBD | TBD | TBD | TBD |
| TBD | TBD | TBD | TBD | TBD | TBD |
| TBD | TBD | TBD | TBD | TBD | TBD |
| TBD | TBD | TBD | TBD | TBD | TBD |
| TBD | TBD | TBD | TBD | TBD | TBD |

## What Changed

### Database & Queries
- Trimmed question index and show selects to only required columns.
- Converted question show eager loads to avoid loading votes for guests.
- Replaced `whereDate()` with ranged `created_at` filters for index usage.
- Collapsed RAG vector retrieval to a single ordered query.
- Added supporting indexes for list filtering and joins.

### Caching
- Short-lived cache for questions index results by filter signature.
- Cached categories/tags lists with admin-side invalidation.
- Cached unread notification counts with invalidation on read.

### Inertia Payload Reduction
- Mapped question index payloads to minimal fields.
- Paginated project knowledge items and scoped tab payloads.

### Background / Realtime
- Broadcast events queued and dispatched after commit to reduce request latency.

## Before/After Metrics (Phase 2–4)

Collect before/after using the perf logs and browser network sizes.

| Route | Metric | Before | After |
| --- | --- | --- | --- |
| `questions.index` | Avg ms | TBD | TBD |
| `questions.index` | Avg queries | TBD | TBD |
| `questions.index` | Payload (KB) | TBD | TBD |
| `questions.show` | Avg ms | TBD | TBD |
| `questions.show` | Avg queries | TBD | TBD |
| `questions.show` | Payload (KB) | TBD | TBD |
| `projects.show` (knowledge/ask) | Avg ms | TBD | TBD |
| `projects.show` (knowledge/ask) | Avg queries | TBD | TBD |
| `projects.show` (knowledge/ask) | Payload (KB) | TBD | TBD |

## Indexes Added (SQL)

```
CREATE INDEX questions_created_at_idx ON questions (created_at);
CREATE INDEX answers_created_at_idx ON answers (created_at);
CREATE INDEX bookmarks_question_id_idx ON bookmarks (question_id);
CREATE INDEX comments_user_id_idx ON comments (user_id);
CREATE INDEX question_tag_tag_id_idx ON question_tag (tag_id);
```

## Risks & Tradeoffs

- Short-lived caches can show stale list data for up to 45–60s.
- Category/tag caches are invalidated on admin writes; manual DB changes require cache clear.
- Queued broadcasts require a running queue worker to deliver real-time updates.
