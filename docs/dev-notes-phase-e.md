# Dev Notes · Phase E

## Decisions & Assumptions
- Category names are globally unique for simplicity; hierarchy depth is unconstrained. Slugs are auto-generated and unique.
- Category delete is **restricted** when children exist to avoid silent reassignment; questions under a deleted leaf category are set to `NULL`.
- Tags are admin-managed only; members/moderators can only select existing tags (no creation on the fly).
- Tag delete detaches pivot rows automatically.
- Tag filter uses **AND** semantics (question must have all selected tags) to improve precision.
- Answered status definition: `answers_count > 0` (computed via `withCount`).
- Search uses PostgreSQL `websearch_to_tsquery` with weighted vectors; SQLite/dev test fallback uses `LIKE` to keep tests green.
- Query logic centralized in `App\Queries\QuestionIndexQuery` to keep controllers slim and reusable in future API endpoints.

## Future Phase Alignment
- **Phase F (notifications)**: taxonomy metadata already available on questions payloads; notification templates can reference category/tag names without extra queries.
- **Phase G (realtime)**: search/filter query builder is isolated, making it straightforward to expose via broadcasting or websockets without controller rewrites.
- **Activity log hardening**: taxonomy changes are centralized in admin controllers, easing future auditing hooks.
