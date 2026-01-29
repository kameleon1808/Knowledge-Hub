# Phase E · Categories, Tags, Filters, Search

## Overview
Phase E introduces hierarchical categories, reusable tags, richer filters, and PostgreSQL-backed full-text search for questions. Admins own taxonomy management while moderators and members can classify their questions during create/edit.

## Data Model
- **categories**: `id`, `parent_id` (nullable, self FK, null on delete), `name` (globally unique), `slug` (unique), `description`, timestamps; indexes on `parent_id`, `slug`.
- **tags**: `id`, `name` (unique), `slug` (unique), timestamps; index on `slug`.
- **question_tag**: `question_id`, `tag_id`, timestamps, PK(`question_id`,`tag_id`).
- **questions**: add `category_id` (nullable FK -> categories, null on delete).
- **search vectors** (PostgreSQL): generated `tsvector` columns on `questions.search_vector` (title + body, weighted) and `answers.search_vector` (body), with GIN indexes.

## Category Rules
- Uniqueness: category `name` is globally unique (simplest governance). Slugs are generated automatically and unique.
- Hierarchy: single parent; no depth enforcement. Helpers: `parent`, `children` relations.
- Delete behavior: deletion **blocked** if children exist. If allowed, any linked questions are decoupled (`category_id` set to null).

## Tag Rules
- Tags are admin-managed only in Phase E; users select from existing tags (no on-the-fly creation).
- Deletion detaches tag from questions, then removes tag record.
- Slugs auto-generated and unique.

## Authorization Matrix
- **Admin**: CRUD categories and tags; can classify any question.
- **Moderator**: cannot manage taxonomy; can classify questions they create/edit (any question per Phase B rules).
- **Member**: cannot manage taxonomy; can classify their own questions.

## UI Changes
- **Question create/edit**: category dropdown (nullable) + multi-select tags (existing only); persisted transactionally with attachments.
- **Question show**: displays category chip and tag chips below the header.
- **Admin**
  - Categories: list with parent/name/slug/child count; create/edit/delete (delete blocked when children exist).
  - Tags: list with search, create/edit/delete (delete detaches from questions).

## Filters (Questions index)
- Query params: `q`, `category` (id), `tags[]` (ids, AND semantics), `status` (`answered|unanswered`), `date_preset` (`last7|last30|last90`), `from`, `to` (YYYY-MM-DD).
- Filter state stays in URL; chips show active filters; "Clear all" resets.
- Tag filter uses **AND** (question must have all selected tags).
- Answered status = `answers_count > 0`.

## Full-Text Search (PostgreSQL)
- `questions.search_vector`: `A` weight for title, `B` for body.
- `answers.search_vector`: `B` weight for body.
- Query uses `websearch_to_tsquery('english', :q)`; matches question vector **or** any answer vector.
- Ranking: `ts_rank(question_vector) + max(ts_rank(answer_vectors))`, then recency tiebreaker.
- Fallback (SQLite/dev tests): `LIKE` search on title/body/answers body.

## Performance Notes
- Indexes: GIN on search vectors; indexes on `category_id`, `parent_id`, `slug`, pivot PK.
- Query builder eager-loads author, category, tags and `answers_count` + vote sum to avoid N+1.
- Filters/search encapsulated in `App\Queries\QuestionIndexQuery` to keep controller thin.
