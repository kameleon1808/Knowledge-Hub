# User Test Plan — Phase G: Real-time Updates (Reverb)

End-to-end manual test plan for Phase G. At least 25 cases, grouped by persona. Each case includes Preconditions, Steps, and Expected Result.

**Prerequisites:** Docker stack running (app, web, db, node, reverb). `.env` has Reverb and VITE_REVERB_* set; browser can reach WebSocket at the configured host/port (e.g. `ws://localhost:8081`).

---

## Guest

**G1. Guest can open home and question list without errors**  
- **Preconditions:** Not logged in; app and Reverb running.  
- **Steps:** Open app home; open Questions list; open a question (read-only).  
- **Expected:** No JavaScript errors; no Echo/WebSocket connection attempted; page loads normally.

**G2. Guest viewing question page does not see Echo or broadcast errors**  
- **Preconditions:** Not logged in; on a question show page.  
- **Steps:** Stay on page; open devtools console.  
- **Expected:** No Echo initialization or subscription errors; no WebSocket connection (Echo not used for guests).

**G3. Guest cannot subscribe to private question channel**  
- **Preconditions:** Not logged in.  
- **Steps:** Attempt to open a URL or use a tool that would subscribe to `private-question.1` (e.g. via /broadcasting/auth with no session).  
- **Expected:** Authorization fails (401/403); no subscription.

**G4. Guest can browse categories and tags**  
- **Preconditions:** Not logged in.  
- **Steps:** Use filters on Questions; open a question.  
- **Expected:** No real-time updates; no Echo errors; standard server-rendered behaviour.

**G5. Guest sees static content on question page**  
- **Preconditions:** Not logged in; question has answers and votes.  
- **Steps:** Open question show page.  
- **Expected:** Question, answers, and scores render from initial load; no live updates (expected for guest).

---

## Member

**M1. Member sees new answer appear without refresh (two browsers)**  
- **Preconditions:** Member A logged in on Browser 1 on question show page; Member B logged in on Browser 2 (or incognito).  
- **Steps:** Browser 2 posts an answer on the same question.  
- **Expected:** Browser 1 sees the new answer appear in the list within a few seconds without refresh; optional “New answer” highlight.

**M2. Member sees vote score update without refresh (two browsers)**  
- **Preconditions:** Member A on question show page in Browser 1; Member B in Browser 2.  
- **Steps:** Browser 2 upvotes or downvotes the question (or an answer).  
- **Expected:** Browser 1 sees the score for that question/answer update without refresh.

**M3. Member sees own vote reflected then live update from another user**  
- **Preconditions:** Member A on question show page; Member B can vote.  
- **Steps:** Member A upvotes question; then Member B upvotes the same question.  
- **Expected:** Member A sees own vote (score +1); then score updates again when B votes (e.g. +2) without refresh.

**M4. Member sees notification badge increment when someone answers their question**  
- **Preconditions:** Member A logged in (e.g. on Dashboard or another page); Member B logged in. Member A has a question; Member B posts an answer on it.  
- **Steps:** Member B posts answer on Member A’s question.  
- **Expected:** Member A’s header notification badge increments without refresh; optional small toast.

**M5. Member can open notifications and badge decreases**  
- **Preconditions:** Member has unread notifications; badge shows N.  
- **Steps:** Open Notifications page; mark as read (or mark all read).  
- **Expected:** Badge updates (e.g. decreases); no Echo errors.

**M6. Member on question page does not get duplicate answers when posting**  
- **Preconditions:** Member on question show page.  
- **Steps:** Post an answer (form submit).  
- **Expected:** Answer appears once (from redirect/Inertia response); if broadcast is received, duplicate is not added (e.g. de-duplicated by id).

**M7. Member can post answer and see it and optional “New answer” highlight**  
- **Preconditions:** Member on question show page.  
- **Steps:** Post an answer.  
- **Expected:** Answer appears; optional brief “New answer” style highlight; no errors.

**M8. Member removing vote sees score update**  
- **Preconditions:** Member has voted on a question or answer; on question page.  
- **Steps:** Remove vote (toggle off).  
- **Expected:** Score updates (e.g. -1); in two-browser scenario, other viewer sees score update without refresh.

**M9. Member on another page then opens question sees correct data**  
- **Preconditions:** Member was on Dashboard; question has received new answers/votes from others.  
- **Steps:** Open question show page.  
- **Expected:** Full server-rendered data (answers, scores) correct; then any further updates arrive via WebSocket.

**M10. Member can use bookmarks and comments while real-time is active**  
- **Preconditions:** Member on question page; real-time connected.  
- **Steps:** Toggle bookmark; add a comment.  
- **Expected:** Bookmark and comment work; no Echo errors; real-time (answers/votes) still work.

**M11. Member receives only their own notification channel**  
- **Preconditions:** Member A and Member B exist; A knows B’s user id (e.g. from URL).  
- **Steps:** (Manual or via devtools) Attempt to subscribe as A to channel `user.{B_id}.notifications`.  
- **Expected:** Server denies authorization (e.g. 403); A does not receive B’s notifications.

**M12. Member notification badge syncs after marking read**  
- **Preconditions:** Member has unread notifications; badge shows N.  
- **Steps:** Open notifications; mark one or all as read.  
- **Expected:** Badge count updates to match server (e.g. via Inertia props); no conflict with future NotificationCreated (badge should reflect server or event, not double-count).

---

## Moderator

**MO1. Moderator sees new answer without refresh (same as Member)**  
- **Preconditions:** Moderator on question show page; another user posts answer.  
- **Steps:** Other user posts answer.  
- **Expected:** New answer appears without refresh; optional “New answer” highlight.

**MO2. Moderator sees vote updates without refresh**  
- **Preconditions:** Moderator on question show page; others vote.  
- **Steps:** Another user votes on question or answer.  
- **Expected:** Score updates without refresh.

**MO3. Moderator sees notification badge when someone answers their question**  
- **Preconditions:** Moderator has a question; another user posts answer.  
- **Steps:** Other user posts answer.  
- **Expected:** Notification badge increments without refresh.

**MO4. Moderator can access Moderator dashboard and real-time still works**  
- **Preconditions:** Moderator logged in; previously on question page with Echo connected.  
- **Steps:** Navigate to Moderator dashboard; then back to a question.  
- **Expected:** No Echo errors; when back on question page, real-time works again.

**MO5. Moderator and Member both see same live answer**  
- **Preconditions:** Moderator (Browser 1) and Member (Browser 2) on same question page.  
- **Steps:** Third user posts answer.  
- **Expected:** Both Browser 1 and Browser 2 show the new answer without refresh.

---

## Admin

**A1. Admin sees new answer without refresh**  
- **Preconditions:** Admin on question show page; another user posts answer.  
- **Steps:** Other user posts answer.  
- **Expected:** New answer appears without refresh.

**A2. Admin sees vote and notification updates**  
- **Preconditions:** Admin on question page and/or with unread notifications.  
- **Steps:** Others vote or answer Admin’s question.  
- **Expected:** Score and notification badge update without refresh.

**A3. Admin can use Admin panel and return to Q&A with real-time working**  
- **Preconditions:** Admin logged in.  
- **Steps:** Open Admin panel; then open a question.  
- **Expected:** No Echo errors; real-time (answers, votes) works on question page.

**A4. Admin and Member cannot receive each other’s notification channel**  
- **Preconditions:** Admin (user A) and Member (user B) logged in.  
- **Steps:** Attempt (e.g. via crafted request) to authorize as A for channel `user.{B_id}.notifications` and as B for `user.{A_id}.notifications`.  
- **Expected:** Both authorization attempts fail (403); neither receives the other’s notifications.

---

## Cross-cutting: network and behaviour

**N1. Reconnect after network drop**  
- **Preconditions:** Member on question page; WebSocket connected.  
- **Steps:** Disable network (or throttle to offline) for a few seconds; then restore.  
- **Expected:** Echo/Pusher reconnects (optional brief “reconnecting” state); no crash; after reconnect, new events (e.g. new answer, vote) are received again.

**N2. No crash when Reverb is stopped then restarted**  
- **Preconditions:** Member on question page; Reverb running.  
- **Steps:** Stop Reverb container; wait a few seconds; start Reverb again.  
- **Expected:** Frontend may show disconnected state; after Reverb is back, reconnection succeeds; no uncaught exception or app crash.

**N3. Guest experience: no Echo initialization errors**  
- **Preconditions:** Not logged in; app and Reverb running.  
- **Steps:** Navigate home, questions list, question show.  
- **Expected:** No Echo/WebSocket errors in console; Echo not initialized for guests.

**N4. Unauthorized subscription: user A cannot receive user B notifications**  
- **Preconditions:** User A and User B logged in (e.g. two browsers).  
- **Steps:** As User A, attempt to subscribe to `private-user.{B_id}.notifications` (e.g. via devtools or a test request to /broadcasting/auth).  
- **Expected:** Authorization returns 403; User A does not receive events for User B’s notifications.

**N5. Multiple tabs: same user on same question**  
- **Preconditions:** Member logged in; two tabs on same question show page.  
- **Steps:** In another browser/user, post an answer or vote.  
- **Expected:** Both tabs update (new answer or score) without refresh; no duplicate Echo connections causing errors.

---

## Summary

- **Guest:** 5 cases (no Echo, no subscription, static content).  
- **Member:** 12 cases (live answer, vote, notification badge, de-dupe, auth).  
- **Moderator:** 5 cases (same real-time behaviour + dashboard).  
- **Admin:** 4 cases (same real-time + admin panel + channel auth).  
- **Cross-cutting:** 5 cases (reconnect, Reverb restart, guest errors, unauthorized subscription, multiple tabs).

**Total:** 31 test cases.
