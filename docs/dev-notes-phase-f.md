# Dev Notes – Phase F

## Decisions & Assumptions
- Comments use markdown with cached `body_html`; markdown service strips HTML and unsafe links to avoid XSS.
- Bookmark model chosen instead of pure pivot to keep timestamps and counts simple; unique constraint enforces idempotent toggling.
- Notifications use Laravel database channel only; mail implementation stubbed but disabled by `via()`. Unread badge comes from shared Inertia prop to avoid extra AJAX on every page.
- Comment policy: admin/moderator may edit/delete any comment; members limited to own. Bookmark actions allowed for any authenticated role.
- Comment routes return refreshed comment collections for the target entity so SPA can replace lists without reloading the page.
- Seeder uses existing business logic (votes, acceptance) and creates sample comments/bookmarks/notifications idempotently.

## Preparing for Phase G (Real-time) Without Implementing It
- Notification payload already includes `question_id` and `answer_id` suitable for future broadcast events (e.g., `AnswerPosted` channel scoped to question author).
- Comment creation/update/delete responses are JSON-friendly; could be pushed via WebSockets to subscribers without changing shape.
- Bookmark counts returned from toggle endpoints could be broadcast to question channels for live badge updates.
- No server-side event broadcasting configured yet; kept minimal to avoid Phase G scope creep.
- Inertia shared unread count centralizes badge data; swapping to live polling or websockets would only replace this value provider.
