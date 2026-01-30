# Phase H: AI Integration (Provider-Agnostic) and Audit

## Overview

Phase H adds a provider-agnostic AI layer to the Knowledge Hub and implements one AI feature end-to-end: **Generate AI Answer (Draft)** for questions. All LLM communication is audited (tokens and full request/response payloads). The app works without AI configured; when AI is disabled or misconfigured, the UI shows friendly messages and does not crash.

**What was implemented:**

1. **Provider-agnostic AI layer** — Contract `LlmClient`, DTOs (`ChatRequest`, `ChatResponse`), and three providers: OpenAI, Anthropic, Google (Gemini). Provider selection via env.
2. **One AI feature** — Manual “Generate AI Answer (Draft)” on the Question show page (question author + Admin). Optional auto-answer on new question via `AI_AUTO_ANSWER` (default OFF).
3. **Mandatory audit** — Every AI call creates an `ai_audit_logs` row (success or error): token usage, full request/response payloads (no API keys), latency, status.
4. **Fallback behaviour** — AI disabled or provider/key missing returns clear JSON messages (422/503); no answer is created; audit log still created on error when a call is attempted.

## Configuration

### Environment variables

Placeholders live in `backend/.env.example`. Set in `backend/.env`:

| Variable | Description | Default |
|----------|-------------|---------|
| `AI_PROVIDER` | `openai`, `anthropic`, or `gemini` | `openai` |
| `AI_ENABLED` | Enable AI features | `false` |
| `AI_AUTO_ANSWER` | Auto-run AI answer job on new question | `false` |
| `AI_MODEL` | Override model (empty = provider default) | (empty) |
| `AI_TIMEOUT_SECONDS` | HTTP timeout for LLM requests | `30` |
| `AI_MAX_OUTPUT_TOKENS` | Max tokens for completion | `700` |
| `AI_TEMPERATURE` | Sampling temperature | `0.3` |
| `OPENAI_API_KEY` | Required if `AI_PROVIDER=openai` | (empty) |
| `ANTHROPIC_API_KEY` | Required if `AI_PROVIDER=anthropic` | (empty) |
| `GEMINI_API_KEY` | Required if `AI_PROVIDER=gemini` | (empty) |

**Validation:** If `AI_ENABLED=true` and the selected provider has no API key, any AI call throws a clear exception and an audit log entry is created with `status=error`. The UI receives a 503 and shows the message.

### Config file

`config/ai.php` exposes:

- `enabled`, `provider`, `auto_answer`, `model`, `timeout`, `max_output_tokens`, `temperature`
- `providers.openai.key`, `providers.anthropic.key`, `providers.gemini.key`
- Provider default models (e.g. `gpt-4o-mini`, `claude-3-5-haiku-20241022`, `gemini-1.5-flash`)

## Architecture

```
┌──────────────────┐     POST /questions/{id}/ai-answer      ┌─────────────────────┐
│  Question Show   │ ──────────────────────────────────────► │  AiAnswerController  │
│  (Vue/Inertia)   │                                          │  authorize + config  │
└──────────────────┘                                          └──────────┬──────────┘
                                                                          │
                                                                          ▼
┌──────────────────┐     generateForQuestion()                ┌─────────────────────┐
│  AiAnswerService │ ◄─────────────────────────────────────── │  Build ChatRequest   │
│  (prompt build)  │                                          │  (system + user msg)│
└────────┬─────────┘                                          └──────────┬──────────┘
         │                                                                │
         │ generateChatCompletion(request, auditContext)                  │
         ▼                                                                │
┌──────────────────┐     client()          ┌──────────────────┐           │
│  LlmManager      │ ───────────────────► │  OpenAI /        │           │
│  (resolve client)│                       │  Anthropic /     │           │
│  + audit wrap   │ ◄─────────────────── │  Gemini client   │           │
└────────┬─────────┘     ChatResponse      └──────────────────┘           │
         │                                                                 │
         │ logSuccess / logError                                           │
         ▼                                                                 │
┌──────────────────┐                                                      │
│  AiAuditLogger   │ ────────────────────────────────────────────────────┘
│  ai_audit_logs   │   (every call creates one row; no secrets)
└──────────────────┘
```

- **LlmClient contract:** `generateChatCompletion(ChatRequest): ChatResponse`. All providers implement this.
- **LlmManager:** Resolves the active provider from config, validates API key, and wraps the call so that **every** call (success or exception) goes through `AiAuditLogger`.
- **AiAuditLogger:** Writes one row per call: `request_payload` (messages, model, temperature, max_output_tokens; no keys), `response_payload` (raw provider response), `response_text`, token counts, `status` (success/error), `error_message`, `latency_ms`.

## AI Answer flow

### Manual (button)

1. User (question author or Admin) opens Question show page.
2. If `question.can.generate_ai_answer`, a “Generate AI Answer (Draft)” button is shown.
3. On click: POST `/questions/{question}/ai-answer`. Backend checks `AI_ENABLED`; if disabled, returns 422 with message. If enabled, builds prompt, calls `LlmManager->generateChatCompletion` (with audit context), creates an `Answer` attributed to the **AI Assistant** user (`is_system=true`), with `ai_generated=true` and `ai_audit_log_id` set.
4. New answer is returned as JSON and broadcast via `NewAnswerPosted` (Phase G). UI appends the answer and shows “AI” badge and optional “Generated just now”.

### Optional auto-answer (default OFF)

1. When `AI_AUTO_ANSWER=true` and `AI_ENABLED=true`, creating a question dispatches a queued job `GenerateAiAnswerForQuestion`.
2. The job checks `AI_ENABLED`, `AI_AUTO_ANSWER`, and `isConfigured()`; if the question already has an AI-generated answer, it does nothing (idempotent).
3. Otherwise it calls `AiAnswerService->generateForQuestion($question, null)`, creating one answer and one audit log entry.

## Audit log schema and examples

**Table:** `ai_audit_logs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | uuid | Primary key |
| `user_id` | FK nullable | Actor (nullable for job) |
| `subject_type` | string | e.g. `App\Models\Question` |
| `subject_id` | bigint | e.g. question id |
| `provider` | string | `openai`, `anthropic`, `gemini` |
| `model` | string | Model name |
| `request_payload` | json | Messages, model, temperature, max_output_tokens, metadata; **no API keys** |
| `response_payload` | json | Raw provider response |
| `response_text` | text | Normalized text |
| `input_tokens` | int nullable | |
| `output_tokens` | int nullable | |
| `total_tokens` | int nullable | |
| `status` | string | `success` or `error` |
| `error_message` | text nullable | Set when status=error |
| `latency_ms` | int nullable | |
| `created_at` | timestamp | |

**Example request_payload (redacted):** No keys are ever stored. Example shape:

```json
{
  "model": "gpt-4o-mini",
  "messages": [{"role": "system", "content": "You are a helpful assistant..."}, {"role": "user", "content": "Question: ..."}],
  "temperature": 0.3,
  "max_output_tokens": 700,
  "metadata": {"request_id": "ai_...", "user_id": 1, "question_id": 5}
}
```

**Retention:** Audit logs are kept for compliance and debugging. A future phase may add rotation or purge policies; this phase does not implement retention.

## Error handling and fallback

| Situation | HTTP | Behaviour |
|-----------|------|-----------|
| AI disabled (`AI_ENABLED=false`) | 422 | Message: “AI features are disabled…”; no answer; no audit (call not attempted). |
| Provider selected but key missing | 503 | Message from exception; audit log created with `status=error` and `error_message`. |
| Provider returns error (e.g. rate limit) | 502 | Message from exception; audit log created with `status=error`. |
| Any other exception | 500 | Generic message; audit log created if the call was attempted. |

The UI never crashes: all AI endpoints return JSON with a `message`; the frontend shows it (e.g. alert) and keeps the page usable.

## Security notes

- **No secrets in logs:** `request_payload` and `response_payload` must never contain API keys. The logger only stores messages, model, temperature, and max_output_tokens (and raw response from the provider, which does not include keys).
- **Access control:** Only the question author or an Admin can trigger “Generate AI Answer” (`QuestionPolicy::generateAiAnswer`). Unauthorized requests receive 403.
- **System user:** The “AI Assistant” user is seeded with `is_system=true` and a random password; it is not used for login. Answers created by AI are attributed to this user and marked `ai_generated=true` and linked to `ai_audit_log_id`.
