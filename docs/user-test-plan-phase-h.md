# User Test Plan — Phase H: AI Integration and Audit

End-to-end manual test plan for Phase H. At least 25 cases, grouped by persona. Each case includes Preconditions, Steps, and Expected Result.

**Prerequisites:** Docker stack running (app, web, db, node; reverb if testing real-time). Database migrated and seeded (AI Assistant user exists). For AI tests: set `AI_ENABLED=true`, `AI_PROVIDER=openai` (or anthropic/gemini), and the corresponding API key in `backend/.env`.

---

## Guest

**H-G1. Guest does not see Generate AI Answer button**  
- **Preconditions:** Not logged in; open a question show page (e.g. via direct URL if allowed, or after viewing as guest).  
- **Steps:** Open question show page.  
- **Expected:** No “Generate AI Answer (Draft)” button (guest has no question author or admin context).

**H-G2. Guest cannot call AI answer endpoint**  
- **Preconditions:** Not logged in.  
- **Steps:** POST `/questions/1/ai-answer` (e.g. via devtools or curl) without auth.  
- **Expected:** 401 or redirect to login; no answer created; no audit log for that request.

**H-G3. Guest can view question with existing AI answer**  
- **Preconditions:** Not logged in; a question has an answer with “AI” badge.  
- **Steps:** Open that question show page.  
- **Expected:** Question and answers load; AI answer shows “AI” badge and author “AI Assistant”; no errors.

**H-G4. Guest sees static list of questions**  
- **Preconditions:** Not logged in; AI may be enabled on server.  
- **Steps:** Open Questions list; open a question.  
- **Expected:** No AI-related controls; page works as before Phase H.

**H-G5. Guest cannot access admin AI or audit**  
- **Preconditions:** Not logged in.  
- **Steps:** Attempt to open any admin or audit URL that might list AI logs.  
- **Expected:** 401/403 or redirect; no access.

---

## Member

**H-M1. Member (question owner) sees Generate AI Answer button**  
- **Preconditions:** Logged in as Member; AI_ENABLED=true; member owns at least one question.  
- **Steps:** Open one of their questions.  
- **Expected:** “Generate AI Answer (Draft)” button is visible.

**H-M2. Member (question owner) can generate AI answer when AI enabled**  
- **Preconditions:** AI_ENABLED=true; provider and API key set; member owns question; DB seeded (AI Assistant user).  
- **Steps:** Open own question; click “Generate AI Answer (Draft)”.  
- **Expected:** Button shows “Generating…” then new answer appears; answer has “AI” badge and author “AI Assistant”; optional “Generated just now”; no crash.

**H-M3. Member (non-owner) does not see Generate AI Answer button**  
- **Preconditions:** Logged in as Member; open a question created by another user (not Admin).  
- **Steps:** Open that question show page.  
- **Expected:** “Generate AI Answer (Draft)” button is not visible.

**H-M4. Member (non-owner) cannot generate AI answer (403)**  
- **Preconditions:** AI enabled; member A owns question, member B is different user.  
- **Steps:** As member B, POST `/questions/{id}/ai-answer` for member A’s question (e.g. devtools).  
- **Expected:** 403 Forbidden; no answer created; optional audit log if attempt was logged (policy denies before service).

**H-M5. Member sees friendly message when AI disabled**  
- **Preconditions:** AI_ENABLED=false; member owns question.  
- **Steps:** Open own question; click “Generate AI Answer (Draft)” (if button is shown when disabled, otherwise enable UI for test).  
- **Expected:** If button is shown and clicked: response 422 with message like “AI features are disabled”; no answer created; no crash.

**H-M6. Member sees friendly message when provider key missing**  
- **Preconditions:** AI_ENABLED=true; AI_PROVIDER=openai; OPENAI_API_KEY empty (or invalid).  
- **Steps:** As question owner, click “Generate AI Answer (Draft)”.  
- **Expected:** Response 503 with message about configuration; no answer created; one audit log row with status=error and error_message set.

**H-M7. Member can post normal answer after AI answer exists**  
- **Preconditions:** Question has one AI-generated answer.  
- **Steps:** As any member, post a normal (human) answer.  
- **Expected:** Answer appears; no “AI” badge; normal author; no regression.

**H-M8. Member can vote on AI-generated answer**  
- **Preconditions:** Question has AI answer; member is not the question owner (or can vote per policy).  
- **Steps:** Upvote or downvote the AI answer.  
- **Expected:** Score updates; no errors.

**H-M9. Member sees AI badge and author on AI answer**  
- **Preconditions:** At least one answer is AI-generated.  
- **Steps:** Open question; look at answers.  
- **Expected:** AI answer shows “AI” badge and author “AI Assistant”; optional “Generated just now” if just created.

**H-M10. Member (owner) can generate second AI answer manually**  
- **Preconditions:** AI enabled; question already has one AI answer (manual or auto).  
- **Steps:** As owner, click “Generate AI Answer (Draft)” again.  
- **Expected:** New AI answer is created (manual action is allowed; idempotency applies only to auto job).

**H-M11. New answer from AI triggers real-time update (Phase G)**  
- **Preconditions:** Two browsers; member A on question show page with Echo connected; member B (or A in second tab) triggers AI answer.  
- **Steps:** Generate AI answer (as owner or admin).  
- **Expected:** Other viewer sees new answer appear without refresh; “AI” badge and “New answer” style if implemented.

**H-M12. Member normal answer flow unchanged**  
- **Preconditions:** Any member; question without or with AI answers.  
- **Steps:** Post answer via “Your Answer” form.  
- **Expected:** Answer appears as before Phase H; no AI badge; own author; no regression.

---

## Moderator

**H-MO1. Moderator (non-owner) does not see Generate AI Answer button**  
- **Preconditions:** Logged in as Moderator; open a question they do not own.  
- **Steps:** Open question show page.  
- **Expected:** “Generate AI Answer (Draft)” button is not visible (only owner and Admin can).

**H-MO2. Moderator can use normal answer and vote**  
- **Preconditions:** Moderator on question page; AI enabled or disabled.  
- **Steps:** Post answer; vote on question/answer.  
- **Expected:** No regression; no AI button for non-own questions.

**H-MO3. Moderator sees AI answers like any member**  
- **Preconditions:** Question has AI answer.  
- **Steps:** Moderator opens question.  
- **Expected:** AI answer visible with “AI” badge and “AI Assistant”; can vote/comment per policy.

**H-MO4. Moderator cannot call AI answer for others’ questions**  
- **Preconditions:** AI enabled; moderator is not question owner.  
- **Steps:** POST `/questions/{id}/ai-answer` for that question.  
- **Expected:** 403; no answer created.

---

## Admin

**H-A1. Admin sees Generate AI Answer button on any question**  
- **Preconditions:** Logged in as Admin; AI_ENABLED=true.  
- **Steps:** Open any question (own or not).  
- **Expected:** “Generate AI Answer (Draft)” button is visible.

**H-A2. Admin can generate AI answer for any question**  
- **Preconditions:** AI enabled and configured; admin may or may not own question.  
- **Steps:** Open any question; click “Generate AI Answer (Draft)”.  
- **Expected:** New AI answer appears; “AI” badge; author “AI Assistant”; no errors.

**H-A3. Admin sees friendly error when AI disabled**  
- **Preconditions:** AI_ENABLED=false.  
- **Steps:** As admin, click “Generate AI Answer (Draft)” on any question.  
- **Expected:** 422 with “AI features are disabled” message; no answer; no crash.

**H-A4. Admin sees friendly error when provider key missing**  
- **Preconditions:** AI_ENABLED=true; selected provider key empty.  
- **Steps:** As admin, trigger AI answer.  
- **Expected:** 503 with configuration message; audit log row with status=error.

**H-A5. Admin can delete AI-generated answer**  
- **Preconditions:** Question has AI answer; admin has delete permission.  
- **Steps:** Delete the AI answer.  
- **Expected:** Answer removed; no crash; audit log row remains for history.

**H-A6. Audit log created on success**  
- **Preconditions:** AI enabled and configured; admin triggers AI answer.  
- **Steps:** Generate AI answer; check `ai_audit_logs` table (e.g. DB client or admin tool).  
- **Expected:** One row: subject_type=Question, subject_id=question id, status=success, request_payload (no keys), response_payload, response_text, input/output/total_tokens, latency_ms.

**H-A7. Audit log created on provider error**  
- **Preconditions:** AI enabled but provider returns error (e.g. rate limit or invalid key).  
- **Steps:** Trigger AI answer.  
- **Expected:** One row: status=error, error_message set; no answer created.

**H-A8. Auto-answer job does not run when AI_AUTO_ANSWER=false**  
- **Preconditions:** AI_ENABLED=true; AI_AUTO_ANSWER=false; queue worker running.  
- **Steps:** Create a new question (as any user).  
- **Expected:** No AI answer appears automatically; manual button still works.

**H-A9. Auto-answer job runs when AI_AUTO_ANSWER=true**  
- **Preconditions:** AI_ENABLED=true; AI_AUTO_ANSWER=true; provider configured; queue worker running.  
- **Steps:** Create a new question.  
- **Expected:** After job runs, one AI answer appears on that question; idempotent (second run does not add duplicate).

**H-A10. Regression: normal question create and list**  
- **Preconditions:** Admin; AI enabled or disabled.  
- **Steps:** Create question; list questions; open question.  
- **Expected:** No regression; same behaviour as before Phase H for non-AI flows.

---

## Summary

- **Guest:** 5 cases (no button, no endpoint, view AI answer, static list, no audit access).  
- **Member:** 12 cases (owner button, generate, non-owner no button/403, disabled/key missing messages, normal answer, vote, badge, second AI, real-time, regression).  
- **Moderator:** 4 cases (no button for others, normal use, see AI answer, no AI for others).  
- **Admin:** 10 cases (button on any question, generate, errors, delete AI answer, audit success/error, auto-answer off/on, idempotency, regression).  

**Total: 31 test cases.**
