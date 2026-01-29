# Phase D — Dev Notes

## Decisions and Assumptions
- Accepted answer stored on `questions.accepted_answer_id` (preferred approach).
- Only the question author can accept/unaccept an answer; moderators/admins do not bypass this rule (strict enforcement).
- Users cannot vote on their own content (best-practice restriction).
- Polymorphic vote types are stored via a morph map (`question`, `answer`) for clean API payloads.
- Reputation uses an append-only `reputation_events` table with a composite unique key to ensure idempotency.
- Vote/accept operations run inside DB transactions with row-level locks on votes/questions.

## How to Extend Later (without implementing now)
- **Phase E (Search/Filters)**: add aggregated vote scores and accepted answer flags to search indexes or query projections; avoid mixing with reputation logic.
- **Phase G (Real-time)**: broadcast vote and acceptance events via Laravel Echo/Reverb, and update Inertia/Vue stores on WebSocket messages.
- **Moderation tooling**: add admin views for reputation events and vote audit trails; keep `reputation_events` immutable for compliance.
