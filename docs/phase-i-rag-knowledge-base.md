# Phase I: AI Knowledge Base (RAG), Export, and Activity Log

## Overview

Phase I adds a project-based knowledge base with document and email ingestion, vector embeddings, RAG (Retrieval-Augmented Generation) for contextual answers, export to Markdown and PDF, and a general activity log. All AI calls (embeddings and chat) use the provider-agnostic AI layer from Phase H and are audited in `ai_audit_logs`.

## Data Model

### Tables and Relations

**projects**
- `id`, `name`, `description`, `owner_user_id`, `timestamps`
- Owner is the creator; members are stored in `project_user`.

**project_user**
- `project_id`, `user_id`, `role` (owner|member), `timestamps`
- Unique (project_id, user_id).

**knowledge_items**
- `id`, `project_id`, `type` (document|email), `title`, `source_meta` (JSON), `original_content_path` (nullable), `raw_text`, `status` (pending|processed|failed), `error_message`, `timestamps`
- Documents have a stored file path; emails have raw_text set on create.

**knowledge_chunks**
- `id`, `knowledge_item_id`, `chunk_index`, `content_text`, `content_hash`, `embedding` (vector, pgvector), `tokens_count`, `timestamps`
- Unique (knowledge_item_id, chunk_index). Vector index (HNSW, cosine) for similarity search.

**rag_queries**
- `id`, `project_id`, `user_id`, `question_text`, `answer_text`, `cited_chunk_ids` (JSON), `provider`, `model`, `timestamps`

**activity_logs**
- `id`, `actor_user_id`, `action`, `subject_type`, `subject_id`, `project_id`, `metadata` (JSON), `created_at`
- Actions: project.created, knowledge_item.uploaded, knowledge_item.processed, knowledge_item.failed, export.generated, rag.asked.

## Processing Pipeline

1. **Extract** — For documents: read from storage; TXT direct read, DOCX via PHPWord, PDF via smalot/pdfparser. For emails: use existing `raw_text`.
2. **Normalize** — Trim, collapse whitespace, remove null chars, collapse multiple newlines.
3. **Chunk** — ChunkingService: ~1000 chars per chunk, 12% overlap. Content hash (SHA-256) for idempotency.
4. **Embed** — EmbeddingService (via EmbeddingManager) calls the active provider (mock or openai); each call is audited in `ai_audit_logs`.
5. **Store** — Insert `knowledge_chunks` with embedding vector (PostgreSQL) or JSON string (SQLite for tests).

Processing runs asynchronously via `ProcessKnowledgeItemJob`.

## Vector Search Strategy

- **Scope** — Chunks are filtered by `project_id` (via join with `knowledge_items`).
- **Similarity** — Cosine distance (`<=>` in pgvector); HNSW index with `vector_cosine_ops`.
- **TopK** — Default 8 chunks per question.
- **Dimension** — 1536 (configurable via `AI_EMBEDDING_DIMENSION`); must match the embedding model.

## RAG Prompting and Citations

- System message instructs the model to answer only from the provided context and to cite by bracket number (e.g. [1], [2]).
- Context is built from retrieved chunk text with short IDs and source item title.
- Cited chunk IDs are stored in `rag_queries.cited_chunk_ids` and returned to the UI for display.

## AI Audit Requirements

- **Embeddings** — Every embedding call is logged in `ai_audit_logs`: request (model, input_count, input_preview), response (embedding_count, dimensions, total_tokens), status, latency. No full vectors in the log.
- **Chat** — Every RAG chat completion is logged as in Phase H: full request/response payloads (no keys), token counts, status, error_message, latency.
- Subject for RAG flows: `subject_type` = RagQuery, `subject_id` = rag_query.id.

## Export Formats

- **Markdown** — Project title/description, then each knowledge item as a section with metadata and `raw_text`. Stored under `exports/{project_id}/{uuid}.md`, downloaded with correct Content-Type.
- **PDF** — Same content structure rendered as HTML and converted with Dompdf. Stored under `exports/{project_id}/{uuid}.pdf`.
- Each export logs `export.generated` in `activity_logs` with format in metadata.

## Security and Permissions

- **Projects** — Only members (including owner) and admins can view. Only owner or admin can update project or manage members.
- **Knowledge** — Only members with `addKnowledge` (owner/member/admin) can upload documents or add emails.
- **RAG** — Only members (or admin) can ask questions (`askRag`).
- **Export** — Only members (or admin) can export (`export`).
- **Files** — Documents are stored on the `local` (private) disk; download is via controller after authorization.

## Troubleshooting

- **PDF parsing** — Uses smalot/pdfparser (pure PHP). If a PDF fails, check that it is not encrypted or image-only; consider re-exporting as “text” PDF from the source.
- **Embeddings mismatch** — Ensure `AI_EMBEDDING_DIMENSION` matches the embedding model (e.g. 1536 for text-embedding-3-small). Wrong dimension will break vector insert or search.
- **pgvector index** — HNSW requires PostgreSQL with pgvector extension. Migrations skip vector column and index on non-PostgreSQL (e.g. SQLite) for tests.

---

## Dev Notes
- **Parsing:** PDF via smalot/pdfparser; DOCX via PHPWord; TXT direct. Embedding dimension 1536 (OpenAI text-embedding-3-small); config must match provider.
- **Embeddings:** Mock and OpenAI supported; Anthropic no embedding API; Gemini extendable. Chunking: 1000 chars, 12% overlap; no semantic splitting.
- **Activity log:** Separate from `ai_audit_logs`; project-level events (uploads, processing, exports, RAG). AI audit mandatory for all embedding and chat.
- **Performance:** ProcessKnowledgeItemJob queued; vector search project-scoped with HNSW; project show avoids N+1.

---

## User Test Plan (End-to-End)

**Guest:** TC-G1–TC-G5 — No access to projects list, create, view, upload, RAG, or export (401/redirect).

**Member (not owner):** TC-M1 See own projects; TC-M2 View project (tabs visible, no Edit); TC-M3/TC-M4 Cannot edit project or manage members (403); TC-M5 Upload document (Pending → Processed/Failed); TC-M6 Add email item; TC-M7 Ask RAG (citations, activity + ai_audit); TC-M8/TC-M9 Export Markdown/PDF; TC-M10 Activity tab; TC-M11 Cannot delete others’ items (403).

**Owner:** Create project; edit name/description; upload document and email; ask RAG; export Markdown/PDF; view activity; delete own knowledge item; manage members (if UI).

**Admin:** Access any project; same as owner; view all activity; export any project.
