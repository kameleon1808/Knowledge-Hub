# Phase I — Development Notes

## Decisions and Assumptions

- **PDF parsing** — smalot/pdfparser (pure PHP) was chosen so no extra system tools or Docker changes are required. DOCX uses phpoffice/phpword; TXT is read directly.
- **Embedding dimension** — 1536 to match OpenAI text-embedding-3-small. Stored in config and migration; must match the provider’s model.
- **Embedding providers** — Mock and OpenAI are supported for embeddings. Anthropic has no dedicated embedding API in this implementation; Gemini can be added later with the same contract.
- **Chunking** — Fixed 1000 characters and 12% overlap; no semantic splitting. Documented in phase-i-rag-knowledge-base.md.
- **Activity log** — Separate from `ai_audit_logs`; used for project-level events (uploads, processing, exports, RAG asks). AI audit remains mandatory for all embedding and chat calls.

## Performance

- Processing is queued (`ProcessKnowledgeItemJob`) to avoid long request times.
- Vector search is project-scoped and uses an HNSW index for approximate nearest neighbour.
- Project show page loads knowledge items, rag queries, activity logs, and members with explicit queries to avoid N+1.

## Migration and Seed Notes

- Phase I migrations are dated 2026_01_30_200000 and later; they run after Phase H.
- `knowledge_chunks` adds a vector column and HNSW index only when the driver is PostgreSQL; on SQLite a text column is used for tests.
- Seeds (see migrations-and-seeding.md) can create a demo project with sample knowledge items and RAG queries.

## Future Improvements (Not Implemented)

- Retention or purge policy for `ai_audit_logs` and `activity_logs`.
- Re-indexing or “reprocess” for existing knowledge items when the embedding model changes.
- Optional MMR or diversity for retrieved chunks.
- Member management UI (add/remove users to a project) was left as a placeholder; backend policy is in place.
