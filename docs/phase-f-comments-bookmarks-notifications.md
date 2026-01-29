# Phase F – Comments, Bookmarks, Notifications

## Overview
Phase F adds lightweight social features: markdown comments on questions/answers, question bookmarks, and in-app notifications when someone answers your question.

## Data Model
- **comments**: `id`, `user_id`, `commentable_type`, `commentable_id`, `body_markdown`, `body_html`, timestamps; indexes on polymorphic keys. Morph targets: `question`, `answer` (see `Relation::morphMap`).
- **bookmarks**: `id`, `user_id`, `question_id`, timestamps; unique (`user_id`, `question_id`).
- **notifications**: Laravel database notifications; payload: `question_id`, `answer_id`, `actor_user_id`, `question_title`, `snippet`, `read_at`.

## Authorization Matrix
| Role | Comment create | Comment edit/delete | Bookmark toggle | Notification access |
|------|----------------|---------------------|-----------------|---------------------|
| Admin | Yes | Any | Yes | Yes |
| Moderator | Yes | Any | Yes | Yes |
| Member | Yes | Own only | Yes | Yes |
| Guest | No | No | No | No |

## Routes & Payloads
- `POST /comments` `{commentable_type: question|answer, commentable_id, body_markdown}` → `201 {comments: [...]}`
- `PUT/PATCH /comments/{comment}` `{body_markdown}` → `{comments: [...]}`
- `DELETE /comments/{comment}` → `{comments: [...]}`
- `POST /questions/{question}/bookmark` → `{bookmarked: true, bookmarks_count}`
- `DELETE /questions/{question}/bookmark` → `{bookmarked: false, bookmarks_count}`
- `GET /bookmarks` → Inertia page listing bookmarked questions (paginated)
- `GET /notifications` → Inertia page, unread first
- `POST /notifications/{id}/read` → `{success: true}`
- `POST /notifications/mark-all-read` → `{success: true}`
- `GET /notifications/unread-count` → `{unread_count}` (also shared in Inertia props)

## UI Behavior
- **Question show**: bookmark toggle with count; comments section under question; each answer has its own comments block. Inline edit/delete when authorized. Markdown rendered safely.
- **Questions index**: bookmark toggle on cards; badge shows count. Filters unchanged.
- **My Bookmarks**: `/bookmarks` shows saved questions with tags/category, answer count, bookmark count.
- **Notifications**: header bell with unread badge; notifications page highlights unread, links to question, supports mark-one and mark-all read; unread count shared globally.

## Notification Logic
- On answer creation: if responder != question author, send `AnswerPostedOnYourQuestion` via database channel. Payload carries ids, title, and markdown snippet (trimmed to 200 chars). Idempotent per answer (checked in seeder; controller only triggers on create).
- No notification for self-answers. Email channel exists but is disabled by `via` returning only `database`.

## Security Notes
- Comments use markdown -> HTML via `MarkdownService` (`html_input` stripped, unsafe links blocked) preventing XSS. Stored `body_html` cached server-side.
- Routes are auth-protected; policies enforce ownership/moderation for comment mutation; bookmark toggle limited to authenticated users.

## Performance Notes
- Comments ordered by `created_at`; loaded with author via eager loading to avoid N+1.
- Bookmarks and answers load counts via `withCount`; bookmark existence via `withExists` for current user.
- Notifications paginated (15/page) and ordered unread-first then recent. Unread badge uses shared prop (single count query per request).
- All comment/bookmark mutations return minimal JSON payloads for SPA partial updates.
