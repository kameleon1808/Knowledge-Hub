# Phase C — Dev Notes

## Implementation Decisions
- **Markdown rendering**: Server-side conversion with `league/commonmark` using `html_input=strip` and `allow_unsafe_links=false`. This ensures saved HTML is sanitized and safe for `v-html` rendering.
- **Preview experience**: Client-side preview uses `marked` + `DOMPurify` to provide immediate feedback while authoring without additional server calls.
- **Attachments**: Centralized file handling in `AttachmentService` to keep controllers thin and ensure consistent storage/removal logic.
- **Data consistency**: Create/update/delete operations that touch attachments are wrapped in DB transactions.
- **Cached HTML**: `body_html` is stored on create/update for faster read rendering; fallback conversion is used if missing.

## Assumptions
- `storage:link` is executed so `/storage/...` URLs can serve `public` disk files.
- User roles are available on `users.role` and populated by Phase B seeders.
- Only images are accepted as attachments; no other file types are in scope.
- Q&A content does not require full-text search, tags, or voting in Phase C.
