# Phase G: Real-time Updates via Laravel Reverb

## Overview

Phase G adds real-time updates to the Knowledge Hub using Laravel Broadcasting with a self-hosted WebSocket server (Laravel Reverb). No new business features were introduced; existing flows (answers, votes, in-app notifications) now push updates to connected clients via WebSockets.

**What became real-time:**

1. **New answers on a question** — When a user posts an answer, all users viewing that question see the new answer appended without refreshing.
2. **Vote score changes** — Upvotes and downvotes (and vote removal) on questions and answers update the score display live for everyone on the question page.
3. **In-app notifications and unread count** — When a user receives a new in-app notification (e.g. “someone answered your question”), the header badge count updates in real time and an optional toast can be shown.

## Architecture

```
┌─────────────┐     HTTP      ┌─────────────┐     dispatch     ┌──────────────────┐
│   Browser   │ ◄────────────► │   Laravel   │ ───────────────► │ Broadcast events  │
│  (Vue/Inertia)               │   (app)     │                  │ NewAnswerPosted  │
└──────┬──────┘                └──────┬──────┘                  │ VoteUpdated      │
       │                              │                          │ NotificationCreated
       │ WebSocket (Pusher protocol)  │ publish                  └────────┬─────────┘
       │                              │                                   │
       ▼                              ▼                                   ▼
┌─────────────┐                ┌─────────────┐                    ┌───────────────┐
│ Laravel     │                │   Reverb    │ ◄───────────────── │   Reverb      │
│ Echo        │ ◄────────────► │   (WS server)│   receive from    │   (WS server) │
│ (frontend)  │                │   port 8080 │   Laravel app     │               │
└─────────────┘                └─────────────┘                    └───────────────┘
```

- **Request → event → broadcast:** User action (e.g. post answer) is handled by Laravel; after the transaction commits, a broadcast event is dispatched. The default broadcaster (Reverb) sends the payload to the Reverb WebSocket server.
- **Reverb → Echo → UI:** Reverb pushes the event to subscribed clients. Laravel Echo (frontend) is subscribed to the relevant private channels; when an event is received, the Vue app updates local state (e.g. appends an answer, updates score, increments notification count).

## Reverb Setup

### Environment variables

Placeholders live in `backend/.env.example`. For local/Docker, set in `backend/.env`:

| Variable | Description | Example (Docker) |
|----------|-------------|------------------|
| `BROADCAST_CONNECTION` | Default broadcaster | `reverb` |
| `REVERB_APP_ID` | Reverb app id (any non-empty string) | `knowledge-hub` |
| `REVERB_APP_KEY` | Reverb app key (client connects with this) | `local-key` |
| `REVERB_APP_SECRET` | Reverb app secret (server-side only) | `local-secret` |
| `REVERB_HOST` | Host Reverb listens on / clients connect to | See below |
| `REVERB_PORT` | Port for client connection | `8080` (Reverb) or `8081` (host) |
| `REVERB_SCHEME` | `http` or `https` | `http` (local) |
| `REVERB_SERVER_HOST` | Bind address for Reverb process | `0.0.0.0` |
| `REVERB_SERVER_PORT` | Port Reverb process listens on | `8080` |

**Frontend (Vite / browser):**

| Variable | Description |
|----------|-------------|
| `VITE_REVERB_APP_KEY` | Same as `REVERB_APP_KEY` |
| `VITE_REVERB_HOST` | Host the **browser** uses (e.g. `localhost` in Docker) |
| `VITE_REVERB_PORT` | Port the **browser** uses (e.g. `8081` if Reverb is exposed as 8081) |
| `VITE_REVERB_SCHEME` | Same as `REVERB_SCHEME` |

**Docker:** The app container sends broadcasts to Reverb using the service name. The browser runs on the host, so it must connect to the host and the exposed port:

- In **app** container: `REVERB_HOST=reverb`, `REVERB_PORT=8080`.
- For **browser**: `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=8081` (if Reverb is mapped to 8081).

### Docker services

The `reverb` service runs `php artisan reverb:start --host=0.0.0.0 --port=8080` inside a container built from the same PHP image as the app. It shares the app code and env; port `8081:8080` exposes the WebSocket to the host so the browser can connect to `ws://localhost:8081`.

```yaml
reverb:
  build: ...
  command: php artisan reverb:start --host=0.0.0.0 --port=8080
  ports:
    - '8081:8080'
  depends_on:
    - app
    - db
  environment:
    APP_ENV: local
    APP_DEBUG: 'true'
```

### Ports

- **8080** — Nginx (HTTP app).
- **8081** — Reverb WebSocket (host). Inside the Reverb container the server listens on 8080.

## Channels and authorization

All real-time channels used in Phase G are **private**; subscription requires authentication and authorization via `routes/channels.php`.

| Channel | Authorization | Purpose |
|---------|---------------|---------|
| `question.{questionId}` | Any authenticated user; question must exist | New answers and vote updates for that question |
| `user.{userId}.notifications` | Only the user with that `userId` | In-app notification and unread count updates |

Authorization is enforced server-side at `/broadcasting/auth`. Echo sends the channel name and socket id; Laravel runs the channel callbacks and returns 200 only if the user is allowed to subscribe.

## Event payload contracts

### NewAnswerPosted

- **Channel:** `private-question.{questionId}`
- **Event name (broadcastAs):** `NewAnswerPosted`
- **Payload (broadcastWith):**

```json
{
  "id": 123,
  "question_id": 1,
  "body_html": "<p>...</p>",
  "created_at": "2026-01-30T12:00:00.000000Z",
  "author": { "id": 2, "name": "Jane", "reputation": 50 },
  "score": 0,
  "is_accepted": false,
  "attachments": [],
  "comments": [],
  "can": { "update": false, "delete": false, "vote": true }
}
```

### VoteUpdated

- **Channel:** `private-question.{questionId}`
- **Event name:** `VoteUpdated`
- **Payload:**

```json
{
  "votable_type": "question",
  "votable_id": 1,
  "new_score": 5
}
```

### CommentPosted

- **Channel:** `private-question.{questionId}`
- **Event name (broadcastAs):** `CommentPosted`
- **Payload (broadcastWith):**

```json
{
  "id": 45,
  "body_html": "<p>...</p>",
  "body_markdown": "...",
  "created_at": "2026-01-30T12:00:00.000000Z",
  "author": { "id": 2, "name": "Jane" },
  "can": { "update": false, "delete": false },
  "commentable_type": "answer",
  "commentable_id": 7
}
```

`commentable_type` is `"question"` or `"answer"`; `commentable_id` is the question or answer id so the frontend can append the comment to the correct list.

### NotificationCreated

- **Channel:** `private-user.{userId}.notifications`
- **Event name:** `NotificationCreated`
- **Payload:**

```json
{
  "notification_id": "uuid",
  "type": "App\\Notifications\\AnswerPostedOnYourQuestion",
  "data": { "question_id": 1, "answer_id": 2, "question_title": "...", "snippet": "..." },
  "created_at": "2026-01-30T12:00:00.000000Z",
  "unread_count": 3
}
```

## Frontend Echo setup

- **Initialization:** `resources/js/lib/echo.js` exports `getEcho()`. It creates a single Echo instance (Reverb + Pusher protocol) using `VITE_REVERB_*` and `/broadcasting/auth` for private channels. Echo is only created when the key and host are set; guests never call `getEcho()`, so no Echo errors for unauthenticated users.
- **Question show page:** When the user is authenticated and viewing a question, the page subscribes to `private-question.{id}` and listens for `.NewAnswerPosted` (append answer, optional “New answer” highlight) , `.VoteUpdated` (update question/answer score in place), and `.CommentPosted` (append new comment to question or answer comments; skipped for the current user who just posted).
- **Header:** Authenticated layout subscribes to `private-user.{id}.notifications` and listens for `.NotificationCreated`; it updates a reactive unread count and can show a small toast.

No full reload on events; only local state is updated.

## Troubleshooting

- **WebSocket connection failed / mixed content:** Ensure the page is loaded over the same scheme as Reverb (e.g. `http` → `ws://`, not `wss://`). If the app is on `http://localhost:8080`, use `VITE_REVERB_SCHEME=http` and `VITE_REVERB_PORT=8081`.
- **401 on /broadcasting/auth:** Session and CSRF must be sent. Use same domain and ensure cookies are sent (no cross-origin without CORS/cookie config). For SPA + Reverb on same app, same domain is typical.
- **Reverb not receiving events:** Confirm `BROADCAST_CONNECTION=reverb` and that the app and Reverb share the same `REVERB_APP_*` credentials. If using a queue, run a worker so broadcast jobs are processed (or use `ShouldBroadcastNow` for synchronous broadcast).
- **Port already in use:** Change `REVERB_SERVER_PORT` and the host mapping (e.g. `8082:8080`) and set `VITE_REVERB_PORT=8082`.
- **Guests:** Echo is only initialized when `getEcho()` is called, and that happens only in authenticated layout and on the question page when logged in. Guests do not subscribe to private channels, so no Echo errors for them.

---

## Dev Notes
- **Question channel:** private; only authenticated users; question must exist. Reverb in same container image as app; port 8081:8080.
- **Broadcast after commit:** `NewAnswerPosted` uses `ShouldDispatchAfterCommit`; others use `ShouldBroadcastNow`. `CommentPosted` on `question.{id}`; author skips duplicate from broadcast.
- **Notification broadcast:** only for “answer on your question”. Echo only when authenticated; unread count from server + events.
- **Channel auth:** `question.{id}` any authenticated; `user.{userId}.notifications` only that user. No sensitive data in payloads. Phase H can add AI events on same channel model.

---

## User Test Plan (End-to-End)

**Guest:** G1–G5 — Home/questions without Echo; no subscription; static content; no private channel; no errors.

**Member:** M1 New answer without refresh (two browsers); M2 Vote score update without refresh; M3 Own vote then live update; M4 Notification badge increment; M5 Mark read, badge decreases; M6 No duplicate answer when posting; M7/M8 Vote remove/update; M9 Correct data on open; M10 Bookmarks/comments with real-time; M11 Cannot subscribe to other user’s notifications (403); M12 Badge sync after mark read.

**Moderator:** MO1–MO5 — Same real-time (answer, vote, notification); dashboard then back; two browsers see same live answer.

**Admin:** A1–A4 — Same real-time; admin panel then back; channel auth (A cannot receive B’s notifications).

**Cross-cutting:** N1 Reconnect after network drop; N2 Reverb stop/restart; N3 Guest no Echo errors; N4 Unauthorized subscription 403; N5 Multiple tabs update.
