# Dev Notes — Phase H: AI Integration

## Decisions and assumptions

### Provider API shapes

- **OpenAI:** Implemented end-to-end using Laravel HTTP client. Endpoint: `POST https://api.openai.com/v1/chat/completions`. Request: `model`, `messages`, `temperature`, `max_tokens`. Response: `choices[0].message.content`, `usage.prompt_tokens`, `usage.completion_tokens`, `usage.total_tokens`. No assumptions beyond the public API.
- **Anthropic:** Structurally complete client. Endpoint: `POST https://api.anthropic.com/v1/messages`. Request: `model`, `max_tokens`, `messages` (and optional `system`). Messages use `role` (user/assistant) and `content` (string). System message is extracted from the first message with `role=system` and sent as top-level `system`. Response: `content[]` (text blocks), `usage.input_tokens`, `usage.output_tokens`. If the API version or field names change, the client may need small adjustments; documented here for maintainability.
- **Gemini:** Structurally complete client. Endpoint: `POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key=...`. Request: `contents[]` (role + parts[].text), `generationConfig` (temperature, maxOutputTokens). Response: `candidates[0].content.parts[0].text`, `usageMetadata.promptTokenCount`, `usageMetadata.candidatesTokenCount`, `usageMetadata.totalTokenCount`. Assumption: single-turn or multi-turn with role “user” / “model”. If Google changes the API, update the client and this note.

### Audit and logging

- Every LLM call goes through `LlmManager->generateChatCompletion`, which always calls `AiAuditLogger` (success or error). No direct provider calls elsewhere.
- Request payload stored in audit log excludes any header or secret; only body fields (messages, model, temperature, max_output_tokens, metadata) are stored.
- When the provider is not configured (e.g. key missing), we still create an audit log with `status=error` so that misconfiguration attempts are visible.

### AI Assistant user

- One system user (“AI Assistant”) is seeded with `is_system=true` and a random password. All AI-generated answers are attributed to this user. No login or session for this user.
- If the seeder has not run, `User::aiAssistant()` returns null and the service throws a clear runtime exception; run `php artisan db:seed` to fix.

### Idempotency

- **Manual:** User can trigger “Generate AI Answer” multiple times; each click creates a new answer (and audit log). No server-side limit in Phase H.
- **Auto-answer job:** Runs at most one AI answer per question. If the question already has an `ai_generated` answer, the job exits without calling the LLM.

## How this prepares Phase I (RAG) without implementing it

- **Abstraction:** `LlmClient` and `ChatRequest`/`ChatResponse` are generic; RAG can build different prompts (e.g. with retrieved chunks) and still use the same `generateChatCompletion` path and audit.
- **Audit:** All LLM calls already flow through one place and create audit logs; RAG will add more rows (e.g. subject_type still Question or a new RAG-specific subject) without new audit plumbing.
- **Config:** Provider, model, timeout, and token limits are centralised in `config/ai.php`; RAG can reuse or extend the same config.
- **No RAG in Phase H:** No embeddings, no document uploads, no vector search, no chunk retrieval. Phase H only uses the question title, body, optional category/tags, and a short excerpt of existing answers in the prompt.

## Testing

- Phase H feature tests use `Http::fake()` for OpenAI so no real API key is needed in CI. Anthropic and Gemini are not faked in the default test set; add similar fakes if needed.
- CSRF is disabled for the Phase H test class via `withoutMiddleware(ValidateCsrfToken::class)` so that `postJson` succeeds without session CSRF token.
