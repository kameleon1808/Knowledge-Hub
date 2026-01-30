# Phase C — Q&A Core

## Overview
Phase C delivers the core Q&A workflow for authenticated users: create, edit, and delete questions; create, edit, and delete answers; Markdown authoring with preview; and image uploads for both questions and answers. RBAC from Phase B is enforced server-side across all actions.

## Data Model
**Tables**
- `questions`
  - `id`, `user_id`, `title`, `body_markdown`, `body_html` (cached), `created_at`, `updated_at`
- `answers`
  - `id`, `question_id`, `user_id`, `body_markdown`, `body_html` (cached), `created_at`, `updated_at`
- `attachments`
  - `id`, `attachable_type`, `attachable_id`, `user_id`, `disk`, `path`, `original_name`, `mime_type`, `size_bytes`, `created_at`, `updated_at`

**Relationships**
- `Question` belongs to `User` (author)
- `Question` has many `Answer`
- `Question` morphs many `Attachment`
- `Answer` belongs to `Question`
- `Answer` belongs to `User` (author)
- `Answer` morphs many `Attachment`

**Indexes**
- `questions.user_id`
- `answers.question_id`, `answers.user_id`
- `attachments.attachable_type`, `attachments.attachable_id`, `attachments.user_id`

## Authorization Matrix
| Action | Admin | Moderator | Member |
| --- | --- | --- | --- |
| View questions/answers | ✅ | ✅ | ✅ |
| Create question | ✅ | ✅ | ✅ |
| Edit/delete any question | ✅ | ✅ | ❌ |
| Edit/delete own question | ✅ | ✅ | ✅ |
| Create answer | ✅ | ✅ | ✅ |
| Edit/delete any answer | ✅ | ✅ | ❌ |
| Edit/delete own answer | ✅ | ✅ | ✅ |

Policies are enforced in controllers via `authorize()` / `authorizeResource()`; UI visibility mirrors policy decisions but is not relied on for security.

## Routes
| Method | URI | Name | Description |
| --- | --- | --- | --- |
| GET | `/questions` | `questions.index` | List questions with pagination |
| GET | `/questions/create` | `questions.create` | Question form |
| POST | `/questions` | `questions.store` | Create question |
| GET | `/questions/{question}` | `questions.show` | View question + answers |
| GET | `/questions/{question}/edit` | `questions.edit` | Edit question |
| PUT/PATCH | `/questions/{question}` | `questions.update` | Update question |
| DELETE | `/questions/{question}` | `questions.destroy` | Delete question |
| POST | `/questions/{question}/answers` | `answers.store` | Create answer |
| GET | `/answers/{answer}/edit` | `answers.edit` | Edit answer |
| PUT/PATCH | `/answers/{answer}` | `answers.update` | Update answer |
| DELETE | `/answers/{answer}` | `answers.destroy` | Delete answer |

All routes require authentication.

## UI Flow (Screenshot Placeholders)
- **Questions Index**: `/questions`
  - Screenshot placeholder: `docs/screenshots/phase-c/questions-index.png`
- **Create Question**: `/questions/create`
  - Screenshot placeholder: `docs/screenshots/phase-c/questions-create.png`
- **Question Detail + Answers**: `/questions/{id}`
  - Screenshot placeholder: `docs/screenshots/phase-c/questions-show.png`
- **Edit Question**: `/questions/{id}/edit`
  - Screenshot placeholder: `docs/screenshots/phase-c/questions-edit.png`
- **Edit Answer**: `/answers/{id}/edit`
  - Screenshot placeholder: `docs/screenshots/phase-c/answers-edit.png`

## Markdown Rendering + XSS Safety
- **Server-side**: Markdown is converted to HTML using `league/commonmark` with `html_input=strip` and `allow_unsafe_links=false`.
- **Client preview**: The editor preview uses `marked` and sanitizes with `DOMPurify` before rendering.
- **Rendering**: UI uses `v-html` only with sanitized HTML (server output or DOMPurify in preview).

## Image Upload Handling
- **Disk**: `public` (configurable via `ATTACHMENTS_DISK`)
- **Paths**:
  - Questions: `questions/{question_id}/{uuid}.{ext}`
  - Answers: `answers/{answer_id}/{uuid}.{ext}`
- **Allowed types**: `jpg`, `jpeg`, `png`, `webp`, `gif`
- **Max size**: `ATTACHMENTS_MAX_SIZE_KB` (default `5120` KB)
- **Edit flow**:
  - Existing attachments are listed and can be removed before save.
  - Removed images are deleted from both DB and disk.
  - New uploads during edit are stored and attached.

## Troubleshooting
- **Images not showing**: ensure `storage:link` has been run and `public/storage` exists.
  - Docker: `make artisan CMD="storage:link"`
- **403 on actions**: verify the user role and ownership; policies block unauthorized edits/deletes.
- **Upload failures**: check `ATTACHMENTS_MAX_SIZE_KB` and file types; confirm `public` disk permissions.
- **Missing markdown output**: ensure migrations ran and `body_html` is populated on create/update.

---

## Dev Notes
- **Markdown rendering**: Server-side with `league/commonmark` using `html_input=strip` and `allow_unsafe_links=false`. Client preview uses `marked` + `DOMPurify`.
- **Attachments**: Centralized in `AttachmentService`; create/update/delete wrapped in DB transactions. Cached `body_html` stored for faster reads.
- **Assumptions**: `storage:link` required; only images accepted; user roles from Phase B seeders.

---

## User Test Plan (End-to-End)

Scope: Q&A CRUD, Markdown preview, image uploads, RBAC.

**Guest:** G1–G5 — Access to `/questions`, question detail, create, answer, edit blocked; redirect to login.

**Member:** M1 Create question; M2 Markdown preview; M3 Upload images on question; M4 Edit question with image removal; M5 Unauthorized edit/delete other’s question (403); M6 Post answer; M7 Unauthorized edit/delete other’s answer (403); M8 Validation empty title/body; M9 Validation invalid upload type (e.g. PDF rejected).

**Moderator:** MOD1 Edit any question; MOD2 Delete any question; MOD3 Edit any answer; MOD4 Delete any answer; MOD5 Upload images while editing.

**Admin:** A1 Create question and answer; A2 Edit/delete any question; A3 Edit/delete any answer; A4 Upload over size limit rejected; A5 Images load from `/storage/...`.
