# Performance Test Plan

## Manual End-to-End Checklist

Use `PERF_LOG=true` and keep an eye on `storage/app/perf_logs.jsonl` plus `php artisan perf:report`.

### Questions Index
1. Visit `questions.index` with no filters.
2. Expected: page renders within target (set locally, e.g. < 500ms on dev hardware).
3. Observe: request duration, query count/time, response payload size in devtools.
4. Repeat with filters: category, tags (2+), status, date range.

### Question Show
1. Open a question with multiple answers and comments.
2. Expected: page renders within target (e.g. < 600ms).
3. Observe: DB query count/time and payload size; ensure no N+1 spikes.

### Project RAG (Ask AI)
1. Open a project and go to “Ask AI”.
2. Submit a question.
3. Expected: API response includes citations; UI remains responsive.
4. Observe: request duration for the RAG ask endpoint and payload size for `projects.show?tab=ask`.

### Project Exports List
1. Open a project and go to “Exports”.
2. Expected: tab switch and page render are fast (< 300ms).
3. Observe: no unnecessary knowledge item payload on non-knowledge tabs.

### Notifications / Realtime
1. Post an answer as another user and confirm:
   - Notification appears
   - Real-time events are delivered
2. Expected: create-answer request time stays under target while broadcasts are queued.

## Automated Regression Checks

Run:

```
php artisan test --filter=PerformanceRegressionTest
```

Coverage:
- Query-count upper bounds for `questions.index`, `questions.show`, and `projects.show`.
- Payload checks: question index excludes heavy fields.
- Project knowledge items are paginated (15 per page).
