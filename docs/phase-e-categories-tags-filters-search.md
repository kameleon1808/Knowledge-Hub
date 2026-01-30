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

---

## Dev Notes
- Category names globally unique; hierarchy unconstrained; delete blocked when children exist; questions decoupled on category delete.
- Tags admin-managed only; no on-the-fly creation; delete detaches from questions. Tag filter AND semantics.
- Answered status = `answers_count > 0`. Search: PostgreSQL `websearch_to_tsquery` + weighted vectors; SQLite fallback `LIKE`. Logic in `QuestionIndexQuery`.

---

## User Test Plan (End-to-End)

**Guest:** View questions list (no create); open question details; admin routes 403/redirect; create question redirect to login; search from index (URL has `q`).

**Member:** Create question with category and tags; edit own classification; invalid tag id validation error; filter by category, multiple tags (AND), status answered/unanswered, date preset (e.g. last 7 days), custom date range; clear all filters; full-text search returns ranked results; combined filters + search.

**Moderator:** Same filters/search; edit question and change category/tags; cannot manage categories/tags in admin (403).

**Admin:** Categories list, create/edit/delete (delete blocked when children); Tags list, create/edit/delete (detaches); assign category/tags on question; filters and search as above.
