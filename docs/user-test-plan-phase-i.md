# Phase I — User Test Plan (End-to-End Manual)

## Scope

Manual end-to-end tests for Phase I: Projects, Knowledge Base (documents and emails), RAG Ask AI, Exports, Activity log, and permissions. At least 40 test cases grouped by persona.

---

## Guest (Not Logged In)

**TC-G1** — Guest cannot access projects list  
- **Preconditions:** User is not logged in.  
- **Steps:** Open `/projects`.  
- **Expected:** Redirect to login; no projects list.

**TC-G2** — Guest cannot create a project  
- **Preconditions:** User is not logged in.  
- **Steps:** POST to create project (e.g. via API or form).  
- **Expected:** 401/redirect to login; no project created.

**TC-G3** — Guest cannot view a project  
- **Preconditions:** User is not logged in; a project exists.  
- **Steps:** Open `/projects/{id}`.  
- **Expected:** Redirect to login; no project content.

**TC-G4** — Guest cannot upload a document  
- **Preconditions:** User is not logged in.  
- **Steps:** Attempt to upload a file to a project knowledge endpoint.  
- **Expected:** 401/redirect; no knowledge item created.

**TC-G5** — Guest cannot ask RAG or export  
- **Preconditions:** User is not logged in.  
- **Steps:** Attempt RAG ask or export for a project.  
- **Expected:** 401/redirect; no RAG/export performed.

---

## Member (Project Member, Not Owner)

**TC-M1** — Member can see projects they belong to  
- **Preconditions:** User is a member (not owner) of at least one project.  
- **Steps:** Log in, go to Projects.  
- **Expected:** List shows only projects where the user is owner or member.

**TC-M2** — Member can view a project they belong to  
- **Preconditions:** User is a member of project P.  
- **Steps:** Open project P show page.  
- **Expected:** Project name, description, tabs (Knowledge Base, Ask AI, Exports, Activity) visible; no Edit or member management.

**TC-M3** — Member cannot edit project settings  
- **Preconditions:** User is a member (not owner) of project P.  
- **Steps:** Open project edit or submit update (name/description).  
- **Expected:** 403 or edit form not shown; project not updated.

**TC-M4** — Member cannot manage project members  
- **Preconditions:** User is a member (not owner) of project P.  
- **Steps:** Attempt to add or remove a member (if UI exists).  
- **Expected:** 403 or UI not shown.

**TC-M5** — Member can upload a document (PDF/DOCX/TXT)  
- **Preconditions:** User is a member of project P; AI/queue configured.  
- **Steps:** In project P Knowledge tab, upload a valid PDF/DOCX/TXT file.  
- **Expected:** File accepted; new knowledge item appears with status Pending; after processing, status becomes Processed (or Failed with message).

**TC-M6** — Member can add an email knowledge item  
- **Preconditions:** User is a member of project P.  
- **Steps:** In Knowledge tab, add email (title, from, sent_at optional, body_text required). Submit.  
- **Expected:** New knowledge item (type email) appears with status Pending; after processing, Processed or Failed.

**TC-M7** — Member can ask RAG question  
- **Preconditions:** User is a member of project P; P has at least one processed knowledge item; AI and embeddings configured.  
- **Steps:** In Ask AI tab, enter a question, submit.  
- **Expected:** Answer returned with optional citations; rag_query record created; entry in activity_logs and ai_audit_logs.

**TC-M8** — Member can export as Markdown  
- **Preconditions:** User is a member of project P.  
- **Steps:** In Exports tab, click “Export as Markdown”.  
- **Expected:** Markdown file downloads; activity_logs has export.generated (format markdown).

**TC-M9** — Member can export as PDF  
- **Preconditions:** User is a member of project P.  
- **Steps:** In Exports tab, click “Export as PDF”.  
- **Expected:** PDF file downloads; activity_logs has export.generated (format pdf).

**TC-M10** — Member sees Activity tab  
- **Preconditions:** User is a member of project P; some activity exists.  
- **Steps:** Open project P, Activity tab.  
- **Expected:** Recent events (uploads, processing, RAG asks, exports) listed with action and timestamp.

**TC-M11** — Member cannot view another project they are not in  
- **Preconditions:** User A is member of P1; P2 exists and A is not a member.  
- **Steps:** As A, open `/projects/{P2}`.  
- **Expected:** 403; no project content.

**TC-M12** — Document upload rejects invalid file type  
- **Preconditions:** User is a member of project P.  
- **Steps:** Try to upload a file that is not PDF/DOCX/TXT (e.g. .exe or .jpg).  
- **Expected:** Validation error; no knowledge item created.

**TC-M13** — Email creation requires body_text  
- **Preconditions:** User is a member of project P.  
- **Steps:** Submit add-email form with empty body_text.  
- **Expected:** Validation error; no knowledge item created.

**TC-M14** — RAG returns “insufficient context” when no chunks  
- **Preconditions:** User is a member of project P; P has no processed chunks (or vector search returns nothing).  
- **Steps:** Ask a question in Ask AI.  
- **Expected:** Answer indicates insufficient context or no relevant context; no crash.

**TC-M15** — Regression: Member can still use Q&A (questions list, create, view)  
- **Preconditions:** User is a member (app role).  
- **Steps:** Go to Questions, create a question, view it.  
- **Expected:** Existing Q&A flow works as before Phase I.

---

## Owner (Project Owner)

**TC-O1** — Owner can create a project  
- **Preconditions:** User is logged in.  
- **Steps:** Projects → New Project; enter name and optional description; submit.  
- **Expected:** Project created; user is owner; redirect to project show; activity_logs has project.created.

**TC-O2** — Owner can edit project name and description  
- **Preconditions:** User is owner of project P.  
- **Steps:** Open project P, Edit; change name/description; save.  
- **Expected:** Project updated; success message.

**TC-O3** — Owner can see Members section  
- **Preconditions:** User is owner of project P.  
- **Steps:** Open project P show page.  
- **Expected:** Members section visible with list of members and roles.

**TC-O4** — Owner can upload documents and add emails  
- **Preconditions:** User is owner of project P.  
- **Steps:** Upload a document and add an email in Knowledge tab.  
- **Expected:** Both items created and processed (or pending/failed with message).

**TC-O5** — Owner can ask RAG and export  
- **Preconditions:** User is owner of project P; P has processed items.  
- **Steps:** Ask AI and export Markdown/PDF.  
- **Expected:** Same as member (RAG answer, export download, activity logged).

**TC-O6** — Owner sees all activity for the project  
- **Preconditions:** User is owner of project P; multiple events (uploads, RAG, export) occurred.  
- **Steps:** Open Activity tab.  
- **Expected:** Events listed; owner can see who performed actions where actor is stored.

**TC-O7** — Owner can delete project (if implemented)  
- **Preconditions:** User is owner of project P; delete is implemented.  
- **Steps:** Delete project P.  
- **Expected:** Project and related data removed or soft-deleted per implementation.

**TC-O8** — Create project then add knowledge and ask RAG  
- **Preconditions:** User logged in; AI and embeddings configured.  
- **Steps:** Create project → add one TXT or email → wait for processed → Ask AI with a question that matches content.  
- **Expected:** Answer reflects content; citations point to the item.

**TC-O9** — Export contains project title and knowledge items  
- **Preconditions:** Project P has name, description, and at least one knowledge item with raw_text.  
- **Steps:** Export as Markdown; open downloaded file.  
- **Expected:** Project title and description; each knowledge item with title and content.

**TC-O10** — Processing failure shows failed status and message  
- **Preconditions:** Upload a file that will fail processing (e.g. corrupted PDF or unsupported format).  
- **Steps:** Upload; wait for job.  
- **Expected:** Knowledge item status = Failed; error_message set; activity_logs has knowledge_item.failed; UI shows failure.

---

## Admin

**TC-A1** — Admin can see all projects  
- **Preconditions:** User is admin; multiple projects exist (some not owned by admin).  
- **Steps:** Go to Projects list.  
- **Expected:** All projects visible (admin bypasses membership filter).

**TC-A2** — Admin can view any project  
- **Preconditions:** User is admin; project P exists; admin is not a member.  
- **Steps:** Open `/projects/{P}`.  
- **Expected:** Project show page; all tabs visible.

**TC-A3** — Admin can edit any project  
- **Preconditions:** User is admin; project P exists; admin is not owner.  
- **Steps:** Edit project P (name/description); save.  
- **Expected:** Project updated; 200/redirect.

**TC-A4** — Admin can upload and add email to any project  
- **Preconditions:** User is admin; project P exists.  
- **Steps:** In project P Knowledge tab, upload file and add email.  
- **Expected:** Items created and processed (or pending/failed).

**TC-A5** — Admin can ask RAG and export for any project  
- **Preconditions:** User is admin; project P exists.  
- **Steps:** Ask AI and export Markdown/PDF for P.  
- **Expected:** Answer and download; activity and AI audit logged.

**TC-A6** — Admin can manage members (if UI exists)  
- **Preconditions:** User is admin; project P exists.  
- **Steps:** Add or remove a member for P.  
- **Expected:** Membership updated; no 403.

**TC-A7** — AI audit entries created for embeddings and chat  
- **Preconditions:** RAG is used (question asked) with embeddings and chat enabled.  
- **Steps:** Ask a question; then check ai_audit_logs (DB or admin UI if exists).  
- **Expected:** At least one log for embedding call (subject RagQuery) and one for chat completion; tokens and payloads stored; no API keys.

**TC-A8** — Regression: Admin can still access admin panel (users, categories, tags)  
- **Preconditions:** User is admin.  
- **Steps:** Go to /admin, manage users/categories/tags.  
- **Expected:** Existing admin features work.

**TC-A9** — AI disabled: RAG returns clear message  
- **Preconditions:** AI_ENABLED=false or provider not configured.  
- **Steps:** As member of a project, try to ask RAG question.  
- **Expected:** 422/503 with message that AI is disabled or not configured; no crash.

**TC-A10** — Embedding provider missing key: processing or RAG fails with audit  
- **Preconditions:** AI_ENABLED=true, AI_PROVIDER=openai, OPENAI_API_KEY empty.  
- **Steps:** Process a knowledge item or ask RAG.  
- **Expected:** Error response; ai_audit_logs has entry with status=error and error_message.

---

## Failure and Regression

**TC-F1** — Bad PDF does not break job  
- **Preconditions:** Queue running; user uploads a corrupted or image-only PDF.  
- **Steps:** Upload; wait for job.  
- **Expected:** Knowledge item marked Failed with error_message; no unhandled exception; activity knowledge_item.failed.

**TC-F2** — Export on empty project  
- **Preconditions:** Project P has no knowledge items.  
- **Steps:** Export as Markdown and PDF.  
- **Expected:** File still generated (project title/description only); download works.

**TC-F3** — RAG with no processed chunks  
- **Preconditions:** Project P has only pending/failed items.  
- **Steps:** Ask RAG question.  
- **Expected:** Answer indicates no/insufficient context; no server error.

**TC-R1** — Questions index still loads  
- **Preconditions:** Phase I deployed.  
- **Steps:** Open Questions list.  
- **Expected:** Questions load with filters and pagination.

**TC-R2** — Question show and answers still work  
- **Preconditions:** Phase I deployed; question with answers exists.  
- **Steps:** Open a question; view answers; post answer (if allowed).  
- **Expected:** Same behaviour as before Phase I.

**TC-R3** — Phase H AI answer (Generate AI Answer) still works  
- **Preconditions:** AI enabled; user is question author or admin.  
- **Steps:** On question show, click Generate AI Answer (Draft).  
- **Expected:** AI answer created; ai_audit_logs has chat entry; UI shows new answer.

---

## Summary

- **Guest:** 5 cases (no access).  
- **Member:** 15 cases (view, upload, email, RAG, export, activity, permission and validation).  
- **Owner:** 10 cases (create, edit, members, full flow, export content, failure handling).  
- **Admin:** 10 cases (all projects, edit any, RAG/export any, audit, regression).  
- **Failure/Regression:** 5 cases.  

**Total: 45 test cases.**
