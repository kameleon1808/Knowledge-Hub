# Phase D — End-to-End User Test Plan

## Guest
**G-01 View question page requires auth**
- Preconditions: Not logged in.
- Steps: Navigate to `/questions`.
- Expected Result: Redirect to login screen.

**G-02 Vote endpoints blocked**
- Preconditions: Not logged in.
- Steps: Send `POST /votes` with any payload.
- Expected Result: 302 redirect to login (web) or 401 (JSON), no vote created.

**G-03 Accept endpoint blocked**
- Preconditions: Not logged in.
- Steps: Send `POST /questions/{id}/accept/{answerId}`.
- Expected Result: 302 redirect to login (web) or 401 (JSON), no accepted answer.

**G-04 Profile route blocked**
- Preconditions: Not logged in.
- Steps: Navigate to `/profile`.
- Expected Result: Redirect to login.

**G-05 Invalid vote type returns 422**
- Preconditions: Not logged in.
- Steps: Send `POST /votes` with `votable_type=invalid`.
- Expected Result: 302 redirect to login (web) or 401 (JSON); if authenticated, 422 validation error.

## Member
**M-01 Upvote a question**
- Preconditions: Logged in as Member A; Question authored by Member B exists.
- Steps: Open question detail; click upvote on question.
- Expected Result: Score increases by 1; upvote button highlights; Member B reputation +5.

**M-02 Downvote a question**
- Preconditions: Logged in as Member A; Question authored by Member B exists.
- Steps: Click downvote on question.
- Expected Result: Score decreases by 1; downvote highlights; Member B reputation -2.

**M-03 Remove a vote (neutral)**
- Preconditions: Member A has upvoted a question.
- Steps: Click upvote again.
- Expected Result: Vote removed; score returns; Member B reputation reverts by -5.

**M-04 Switch vote (up -> down)**
- Preconditions: Member A upvoted a question by Member B.
- Steps: Click downvote.
- Expected Result: Score becomes -1; Member B reputation becomes -2 (removes +5, applies -2).

**M-05 Switch vote (down -> up)**
- Preconditions: Member A downvoted a question by Member B.
- Steps: Click upvote.
- Expected Result: Score becomes +1; Member B reputation becomes +5 (removes -2, applies +5).

**M-06 Upvote an answer**
- Preconditions: Member A views question with an answer by Member B.
- Steps: Click upvote on answer.
- Expected Result: Answer score +1; Member B reputation +10.

**M-07 Downvote an answer**
- Preconditions: Member A views question with an answer by Member B.
- Steps: Click downvote on answer.
- Expected Result: Answer score -1; Member B reputation -2.

**M-08 Cannot vote on own post**
- Preconditions: Member A is author of the question.
- Steps: Attempt to upvote own question.
- Expected Result: Vote disabled in UI and server responds 403 if forced.

**M-09 Accept an answer (author only)**
- Preconditions: Member A authored the question; answers exist.
- Steps: Click “Accept” on an answer.
- Expected Result: Accepted badge shown; `accepted_answer_id` set; answer author +15 reputation.

**M-10 Unaccept an answer**
- Preconditions: Member A authored the question; an answer is accepted.
- Steps: Click “Unaccept” on the accepted answer.
- Expected Result: Accepted badge removed; `accepted_answer_id` null; answer author reputation -15.

**M-11 Switch accepted answer**
- Preconditions: Member A authored the question; Answer 1 accepted; Answer 2 exists.
- Steps: Click “Accept” on Answer 2.
- Expected Result: Answer 1 loses accepted; Answer 2 gains accepted; Answer 1 author -15; Answer 2 author +15.

**M-12 Non-author cannot accept**
- Preconditions: Member A views question authored by Member B.
- Steps: Attempt to accept any answer.
- Expected Result: No accept controls; direct request returns 403.

**M-13 Reputation visible next to posts**
- Preconditions: Member A has reputation changes from votes.
- Steps: View question detail.
- Expected Result: Reputation appears next to question author and each answer author.

**M-14 Reputation visible on profile**
- Preconditions: Member A has non-zero reputation.
- Steps: Open `/profile`.
- Expected Result: Profile shows updated reputation value.

**M-15 Invalid vote payload**
- Preconditions: Logged in as Member A.
- Steps: Send `POST /votes` with `value=0`.
- Expected Result: 422 validation error; no vote created.

**M-16 Invalid votable type**
- Preconditions: Logged in as Member A.
- Steps: Send `POST /votes` with `votable_type=comment`.
- Expected Result: 422 validation error; no vote created.

**M-17 Concurrency: rapid double vote**
- Preconditions: Member A viewing a question in two tabs.
- Steps: Click upvote quickly in both tabs.
- Expected Result: Final state shows a single upvote; score +1; reputation applied once.

## Moderator
**MOD-01 Vote as normal user**
- Preconditions: Logged in as Moderator; question by Member exists.
- Steps: Upvote question.
- Expected Result: Score +1; author reputation +5.

**MOD-02 Cannot accept others’ questions**
- Preconditions: Moderator viewing question authored by Member.
- Steps: Try to accept an answer via API.
- Expected Result: 403 forbidden; no accepted answer change.

**MOD-03 Switch vote on answer**
- Preconditions: Moderator downvoted an answer.
- Steps: Click upvote.
- Expected Result: Answer score +1; answer author reputation becomes +10.

**MOD-04 Remove vote**
- Preconditions: Moderator has voted on an answer.
- Steps: Click same vote again.
- Expected Result: Vote removed; score returns; reputation rolled back.

## Admin
**A-01 Vote as normal user**
- Preconditions: Logged in as Admin; question by Member exists.
- Steps: Upvote question.
- Expected Result: Score +1; author reputation +5.

**A-02 Admin cannot accept others’ questions**
- Preconditions: Admin viewing question authored by Member.
- Steps: Attempt to accept an answer.
- Expected Result: 403 forbidden; accepted answer unchanged.

**A-03 Accept on own question**
- Preconditions: Admin authored a question with answers.
- Steps: Accept an answer.
- Expected Result: Accepted badge shown; answer author +15.

**A-04 Remove vote through API**
- Preconditions: Admin has voted on a question.
- Steps: Send `DELETE /votes` with votable payload.
- Expected Result: Vote removed; score updated; reputation rolled back.
