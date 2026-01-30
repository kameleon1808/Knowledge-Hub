# Dev Notes — Phase G: Real-time (Reverb)

## Decisions and assumptions

- **Question channel: private.** The channel `question.{questionId}` is a **private** channel. Only authenticated users can subscribe; authorization checks that the question exists. We did not use a public channel so that we can rely on session-based auth and avoid exposing question updates to unauthenticated clients. Public would be possible if we wanted guests to see live answers/votes; we chose private for consistency and to keep auth simple.

- **Reverb in same container image.** The Reverb service runs `php artisan reverb:start` in a container built from the same Dockerfile as the app. No separate Reverb image; it shares app code and env. Port 8081 on the host maps to 8080 in the Reverb container so the browser can connect to Reverb from the host.

- **Broadcast after commit.** `NewAnswerPosted` implements `ShouldDispatchAfterCommit` so it is broadcast only after the answer-creation transaction commits. All broadcast events (`NewAnswerPosted`, `VoteUpdated`, `NotificationCreated`, `CommentPosted`) use `ShouldBroadcastNow` so they are sent immediately to Reverb without a queue worker.

- **Comment real-time.** `CommentPosted` is dispatched when a comment is created on a question or an answer. It broadcasts on `question.{questionId}` (same channel as answers/votes), so the question show page receives it and appends the new comment to the correct list (question comments or that answer’s comments). The author does not add the comment again from the broadcast (skipped by frontend when `payload.author?.id === authUser?.id`).

- **`public/hot` after node restart.** When the Vite dev server (node container) restarts, the file `public/hot` can become stale. If the frontend still references it, the page may load a blank screen. Remove it after restarting node: `docker compose exec app rm -f public/hot`, then refresh. Alternatively, run a full frontend build (`npm run build`) so the app uses the built assets instead of HMR.

- **Notification broadcast only for “answer on your question”.** We broadcast `NotificationCreated` only when the in-app notification is created via `AnswerPostedOnYourQuestion` (after posting an answer on someone else’s question). Other notification types could be wired the same way (e.g. listen to `NotificationSent` for `database` channel and dispatch `NotificationCreated`) if we add more in-app notifications later.

- **Echo only when authenticated.** The frontend calls `getEcho()` only in authenticated layout (for user notifications) and on the question show page when the user is logged in. Guests never call `getEcho()`, so we avoid Echo initialization or subscription errors for unauthenticated users.

- **Unread count from server and from events.** The header badge is driven by a ref that is initialized from Inertia shared props (`notifications.unread_count`) and updated on `.NotificationCreated` with `unread_count` from the event. Visiting the notifications page or marking as read updates props, and we sync the ref via a watcher so the badge stays correct.

## Security considerations

- **Channel authorization.** All Phase G channels are private. Authorization is defined in `routes/channels.php` and enforced at `/broadcasting/auth`:
  - `question.{questionId}`: user must be authenticated; we only check that the question exists (any authenticated user can subscribe). Tighter rules (e.g. only if user can view the question) can be added later.
  - `user.{userId}.notifications`: only the user with that `userId` can subscribe. User A cannot subscribe to `user.{B_id}.notifications`; the auth callback returns false and the client gets 403.

- **No sensitive data in payloads.** Broadcast payloads contain only what the UI needs (answer snippet, score, notification type and data). We do not broadcast raw markdown, emails, or internal IDs beyond what is already visible in the UI.

- **CSRF and session.** The broadcasting auth endpoint uses the web middleware (session, CSRF). Echo sends the same cookies as the app; ensure same site and correct `SESSION_DOMAIN`/cookie settings if the frontend and Reverb are on different hosts (not the case in the current Docker setup).

## How this prepares Phase H (AI) without implementing it

- **Event-driven flow.** Phase G establishes an event-driven broadcast flow: something happens (answer, vote, notification) → event dispatched → Reverb → Echo → UI update. Phase H can reuse this pattern for AI-related events (e.g. “AI suggestion ready”, “AI summary generated”) by defining new events and channels (e.g. `user.{id}.ai` or `question.{id}.ai`) and subscribing in the frontend where needed.

- **Channel model.** We already have private channels per user and per question. AI features could use the same channel names or dedicated ones (e.g. `private-user.{id}.ai`) with authorization in `channels.php`, without changing the Reverb or Echo setup.

- **Payload contracts.** Phase G documents event payloads (e.g. in `phase-g-realtime-reverb.md`). Phase H can define similar contracts for AI events so the frontend can consume them consistently (e.g. type, status, result snippet).

- **No AI in Phase G.** No AI, exports, RAG, or provider abstraction were added; Phase G is strictly “make existing things real-time” and does not change business rules beyond adding live updates.
