# Phase F Manual Test Plan
Each test lists Preconditions (PC), Steps (ST), Expected Result (ER). Personas: Guest, Member, Moderator, Admin.

## Guest (6 cases)
1. PC: Logged out. ST: Open question show page. ER: Comments visible read-only; add/edit/delete buttons absent.
2. PC: Logged out. ST: Submit comment form via network tab (blocked by UI). ER: Redirect/login prompt or 302 to login; no comment created.
3. PC: Logged out. ST: Click bookmark toggle on index card. ER: Redirect to login; no bookmark created.
4. PC: Logged out. ST: Visit `/bookmarks`. ER: Redirect to login.
5. PC: Logged out. ST: Visit `/notifications`. ER: Redirect to login.
6. PC: Logged out. ST: Open question with many comments. ER: Pagination/infinite scroll not implemented; list renders; no JS errors.

## Member (17 cases)
7. PC: Authenticated member; existing question by another user. ST: Add comment on question with valid markdown. ER: Comment saved; appears with author/time; HTML rendered.
8. PC: Member; question available. ST: Add comment on answer. ER: Comment saved under that answer.
9. PC: Member with own comment. ST: Edit own comment body. ER: Updated text shown; timestamp unchanged.
10. PC: Member with own comment. ST: Delete comment. ER: Comment removed from list.
11. PC: Member; someone else’s comment present. ST: Attempt edit via UI. ER: Edit button hidden; direct request returns 403.
12. PC: Member; someone else’s comment present. ST: Attempt delete via direct request. ER: 403; comment persists.
13. PC: Member. ST: Submit empty comment body. ER: Validation error displayed; no comment saved.
14. PC: Member. ST: Submit overly long comment (>2000 chars). ER: Validation error.
15. PC: Member; question available. ST: Toggle bookmark on question (index). ER: Star filled; count increments; API returns bookmarked=true.
16. PC: Member; same question bookmarked. ST: Toggle again (remove). ER: Star unfilled; count decrements; record removed.
17. PC: Member; many bookmarks. ST: Visit `/bookmarks`. ER: Paginated list shows titles/categories/tags; links navigate to show page.
18. PC: Member; question answered by another user. ST: Post answer (as other user) then check notification for author. ER: Database notification created; unread badge increments.
19. PC: Member answering own question. ST: Post answer. ER: No notification created for self; unread count unchanged.
20. PC: Member with unread notification. ST: Click bell -> page; mark single notification read. ER: Row unhighlighted; unread badge decrements.
21. PC: Member with multiple unread. ST: Click “Mark all as read”. ER: All mark read; unread badge zero.
22. PC: Member with unread notification. ST: Call `/notifications/unread-count`. ER: JSON shows correct count.
23. PC: Member; question with comments. ST: Verify comments ordered oldest→newest. ER: Order by created_at ascending.

## Moderator (6 cases)
24. PC: Authenticated moderator; other user’s comment exists. ST: Edit comment. ER: Allowed; changes saved.
25. PC: Moderator; other user’s comment exists. ST: Delete comment. ER: Allowed; comment removed.
26. PC: Moderator; bookmarks. ST: Toggle bookmark. ER: Works same as member.
27. PC: Moderator; notifications. ST: Receive notification for own question answered by another user. ER: Notification created and visible.
28. PC: Moderator; try mark read on notification belonging to another user via API. ER: 404/403; no change.
29. PC: Moderator; comments form. ST: Submit empty body. ER: Validation error (same as member).

## Admin (6 cases)
30. PC: Admin; question with multiple comments. ST: Delete any comment. ER: Allowed.
31. PC: Admin; create comment on answer. ER: Saves as admin; displays author name.
32. PC: Admin; bookmark toggle on show page. ER: Works; count updates for all viewers.
33. PC: Admin; notifications page. ST: Pagination to next page when >15 records. ER: Next page loads; ordering unread-first preserved per page.
34. PC: Admin; ensure no email sent by default. ST: Inspect notifications channels or mail log. ER: Only database channel used; no mail.
35. PC: Admin; performance. ST: Load question with many comments/answers. ER: No N+1 queries (inspect logs), reasonable render time.
