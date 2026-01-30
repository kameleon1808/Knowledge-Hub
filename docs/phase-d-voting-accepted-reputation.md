# Phase D — Voting, Accepted Answers, Reputation

## Overview
Phase D adds voting for questions and answers, accepted answers (one per question), and a reputation system with auditability. All actions are enforced server-side with RBAC policies, and UI interactions are built for toggle/switch behavior.

## Data Model
**New tables**
- `votes`
  - `id`, `user_id`, `votable_type`, `votable_id`, `value` (-1 or 1), `created_at`, `updated_at`
  - Unique: (`user_id`, `votable_type`, `votable_id`)
  - Indexes: (`votable_type`, `votable_id`), `user_id`
- `reputation_events`
  - `id`, `user_id` (recipient), `actor_user_id` (actor), `subject_type`, `subject_id`, `event_type`, `points`, `metadata`, timestamps
  - Unique: (`user_id`, `actor_user_id`, `subject_type`, `subject_id`, `event_type`)
  - Indexes: (`subject_type`, `subject_id`), `user_id`

**Schema updates**
- `questions.accepted_answer_id` (nullable FK to `answers.id`, `nullOnDelete`)
- `users.reputation` (integer, default 0)

**Relationships**
- `Question` morphs many `Vote`
- `Answer` morphs many `Vote`
- `User` has many `Vote`
- `User` has many `ReputationEvent`
- `Question` belongs to `Answer` via `accepted_answer_id` (`acceptedAnswer`)

## Reputation Rules
| Event | Points | Notes |
| --- | --- | --- |
| Question upvote received | +5 | Event type `UPVOTE_Q` |
| Answer upvote received | +10 | Event type `UPVOTE_A` |
| Answer accepted | +15 | Event type `ACCEPTED` |
| Downvote received | -2 | Event type `DOWNVOTE` |

## Idempotency Strategy
- Votes are unique per (`user_id`, `votable_type`, `votable_id`), so duplicate votes cannot be inserted.
- Reputation events use a composite unique key to guarantee single application per actor + subject + event type.
- `ReputationService::applyEvent()` increments reputation only when a new event is created; `rollbackEvent()` removes the event and reverses points.
- `VoteService` handles toggle/switch logic:
  - same vote => remove vote + rollback
  - opposite vote => rollback old event, apply new
  - new vote => apply once
- `AcceptanceService` rolls back the previous accepted event and applies the new one, all inside a transaction.

## Routes & API
All routes require authentication.

**Voting**
- `POST /votes`
  - Payload: `{ votable_type: "question"|"answer", votable_id: number, value: 1|-1 }`
  - Response: `{ votable_type, votable_id, score, current_user_vote, reputation: { userId: currentReputation } }`
- `DELETE /votes`
  - Payload: `{ votable_type, votable_id }`
  - Response: `{ votable_type, votable_id, score, current_user_vote, reputation: { userId: currentReputation } }`

**Accepted answer**
- `POST /questions/{question}/accept/{answer}`
  - Response: `{ question_id, accepted_answer_id, reputation: { userId: currentReputation } }`
- `DELETE /questions/{question}/accept`
  - Response: `{ question_id, accepted_answer_id: null, reputation: { userId: currentReputation } }`

## UI Behavior
- Vote buttons on question + each answer show current state and score.
- Clicking the same vote again removes it (neutral).
- Clicking the opposite vote switches it.
- Accepted answer is highlighted with a badge; question author sees Accept/Unaccept toggle.
- Reputation is displayed:
  - on profile page
  - next to question author name
  - next to each answer author name

## Security & Authorization
- Voting: any authenticated role (Admin, Moderator, Member) can vote.
- Own-post voting is blocked (assumption; enforced in policies and services).
- Accepted answer: only the question author can accept/unaccept; moderators/admins do not bypass this rule (strict enforcement).
- All endpoints authorize server-side regardless of UI visibility.

## Performance Considerations
- Scores are aggregated via `withSum` (`votes as score`) to avoid N+1 queries.
- Current user vote state is eager-loaded with constrained `votes` relation.
- Indexes support vote lookups and reputation auditing queries.

---

## Dev Notes
- Accepted answer on `questions.accepted_answer_id`; only question author can accept/unaccept (strict).
- Users cannot vote on own content. Polymorphic votes via morph map (`question`, `answer`).
- Reputation: append-only `reputation_events` with composite unique key for idempotency; vote/accept in transactions with row locks.
- Later: Phase E can add vote scores to search; Phase G can broadcast vote/accept via Reverb; moderation can add audit views.

---

## User Test Plan (End-to-End)

**Guest:** G-01–G-05 — `/questions` and profile require auth; vote/accept endpoints 302/401; invalid vote type 422.

**Member:** M-01 Upvote question (+1 score, author +5 rep); M-02 Downvote question (-2 rep); M-03 Remove vote (neutral); M-04/M-05 Switch vote up↔down; M-06/M-07 Upvote/downvote answer (+10/-2 rep); M-08 Cannot vote on own post (403); M-09 Accept answer (author only, +15 rep); M-10 Unaccept; M-11 Switch accepted answer; M-12 Non-author cannot accept (403); M-13 Reputation next to posts; M-14 Reputation on profile; M-15/M-16 Invalid vote payload/type 422; M-17 Double vote idempotent (single upvote).

**Moderator:** MOD-01 Vote as normal; MOD-02 Cannot accept others’ questions (403); MOD-03/MOD-04 Switch/remove vote.

**Admin:** A-01 Vote as normal; A-02 Cannot accept others’ questions (403); A-03 Accept on own question; A-04 DELETE /votes removes vote and rolls back reputation.
