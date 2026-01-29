# Phase C — End-to-End User Test Plan

> Scope: Q&A core (Questions/Answers CRUD), Markdown preview, image uploads, and RBAC enforcement.

## Guest (Unauthenticated)

**G1 — Access questions list (blocked)**
- Preconditions: Not logged in.
- Steps: Navigate to `/questions`.
- Expected Result: Redirected to login page.

**G2 — Open a question (blocked)**
- Preconditions: Not logged in.
- Steps: Navigate to `/questions/{id}`.
- Expected Result: Redirected to login page.

**G3 — Try to create a question (blocked)**
- Preconditions: Not logged in.
- Steps: Navigate to `/questions/create`.
- Expected Result: Redirected to login page.

**G4 — Try to post an answer (blocked)**
- Preconditions: Not logged in.
- Steps: Submit the answer form endpoint `/questions/{id}/answers`.
- Expected Result: Redirected to login page.

**G5 — Try to edit content directly (blocked)**
- Preconditions: Not logged in.
- Steps: Open `/questions/{id}/edit` or `/answers/{id}/edit`.
- Expected Result: Redirected to login page.

## Member

**M1 — Create a question**
- Preconditions: Logged in as Member.
- Steps: Go to `/questions/create`, fill title + body, submit.
- Expected Result: Redirected to the new question detail page; question is listed on `/questions`.

**M2 — Create a question with Markdown preview**
- Preconditions: Logged in as Member.
- Steps: In create form, enter Markdown, switch to Preview tab.
- Expected Result: Preview displays sanitized HTML output.

**M3 — Upload images on question create**
- Preconditions: Logged in as Member.
- Steps: Attach one or more valid image files; submit question.
- Expected Result: Images show under the question body; files exist under `storage/app/public/questions/{id}/`.

**M4 — Edit own question with image removal**
- Preconditions: Logged in as Member; owns a question with attachments.
- Steps: Open edit page, remove one existing image, add a new one, save.
- Expected Result: Removed image no longer displays and is deleted from disk; new image appears.

**M5 — Unauthorized edit/delete of someone else’s question**
- Preconditions: Logged in as Member; another user owns a question.
- Steps: Attempt to `PUT /questions/{id}` or `DELETE /questions/{id}`.
- Expected Result: HTTP 403; no changes made.

**M6 — Post an answer**
- Preconditions: Logged in as Member; question exists.
- Steps: On question detail, submit answer with Markdown.
- Expected Result: Answer appears in list with rendered Markdown.

**M7 — Unauthorized edit/delete of someone else’s answer**
- Preconditions: Logged in as Member; another user owns an answer.
- Steps: Attempt to `PUT /answers/{id}` or `DELETE /answers/{id}`.
- Expected Result: HTTP 403; no changes made.

**M8 — Validation: empty title/body**
- Preconditions: Logged in as Member.
- Steps: Submit create question form with empty title or body.
- Expected Result: Validation errors shown; request rejected.

**M9 — Validation: invalid upload type**
- Preconditions: Logged in as Member.
- Steps: Upload a PDF or other non-image file.
- Expected Result: Validation error; file rejected.

## Moderator

**MOD1 — Edit any question**
- Preconditions: Logged in as Moderator; question owned by Member.
- Steps: Open edit, update title/body, save.
- Expected Result: Question updates successfully.

**MOD2 — Delete any question**
- Preconditions: Logged in as Moderator; question owned by Member.
- Steps: Delete question.
- Expected Result: Question removed; related answers and attachments removed.

**MOD3 — Edit any answer**
- Preconditions: Logged in as Moderator; answer owned by Member.
- Steps: Open `/answers/{id}/edit`, update body, save.
- Expected Result: Answer updates successfully.

**MOD4 — Delete any answer**
- Preconditions: Logged in as Moderator; answer owned by Member.
- Steps: Delete answer.
- Expected Result: Answer removed.

**MOD5 — Upload images while editing**
- Preconditions: Logged in as Moderator; editing someone else’s question or answer.
- Steps: Add images and save.
- Expected Result: Images are stored and displayed.

## Admin

**A1 — Create question and answer**
- Preconditions: Logged in as Admin.
- Steps: Create a question, then post an answer.
- Expected Result: Both records created and visible.

**A2 — Edit/delete any question**
- Preconditions: Logged in as Admin; question owned by Member.
- Steps: Edit then delete the question.
- Expected Result: Updates apply; deletion removes answers and attachments.

**A3 — Edit/delete any answer**
- Preconditions: Logged in as Admin; answer owned by Member.
- Steps: Edit then delete the answer.
- Expected Result: Updates apply; deletion removes answer attachments.

**A4 — Upload validation for size limits**
- Preconditions: Logged in as Admin; max upload size configured.
- Steps: Upload image larger than `ATTACHMENTS_MAX_SIZE_KB`.
- Expected Result: Validation error and upload rejected.

**A5 — Storage/serving check**
- Preconditions: Logged in as Admin; `storage:link` executed.
- Steps: Open question with attachments in browser.
- Expected Result: Images load from `/storage/...` URLs.
