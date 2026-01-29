# Phase E Manual Test Plan

> Each test lists **Preconditions**, **Steps**, **Expected Result**. Grouped by persona. Use seeded accounts unless stated (admin@ / moderator@ / member@, password: `password`).

## Guest
1. **View questions list without auth**
   - Preconditions: none.
   - Steps: Visit `/questions`.
   - Expected: List renders without create button; filters/search usable but apply redirects to login when attempting restricted actions.

2. **Open question details**
   - Preconditions: at least one question exists.
   - Steps: Click a question title.
   - Expected: Question page shows content, category/tag chips, answers; no edit/delete controls.

3. **Attempt admin route**
   - Preconditions: none.
   - Steps: Visit `/admin/categories`.
   - Expected: 403 or redirect to login (not authorized).

4. **Attempt create question redirect**
   - Preconditions: none.
   - Steps: Visit `/questions/create`.
   - Expected: Redirected to login.

5. **Search from index**
   - Preconditions: questions exist.
   - Steps: Enter keyword in search and click Apply.
   - Expected: Page refreshes with filtered results; URL contains `q` param.

## Member
6. **Create question with category and tags**
   - Preconditions: Logged in as member; categories/tags exist.
   - Steps: Go to Create, select category, select multiple tags, submit.
   - Expected: Question created; saved category/tag assignments displayed on show page.

7. **Edit own question classification**
   - Preconditions: Member has a question.
   - Steps: Edit question, change category and tags, save.
   - Expected: Assignments updated on show page.

8. **Cannot assign invalid tag**
   - Preconditions: Logged in member.
   - Steps: Manipulate request (DevTools) to include nonexistent tag id, submit create.
   - Expected: Validation error on tags.

9. **Filter by own category**
   - Preconditions: Multiple questions with different categories.
   - Steps: On index, choose a category, Apply.
   - Expected: Results include only that category; chip shows selected category.

10. **Filter by multiple tags (AND)**
   - Preconditions: One question with Tag A+B, another with Tag A only.
   - Steps: Select Tag A and Tag B, Apply.
   - Expected: Only question with both tags remains.

11. **Status filter answered**
   - Preconditions: At least one question with answers and one without.
   - Steps: Set status=Answered, Apply.
   - Expected: Only questions with answers_count > 0 shown; chip displays Status: answered.

12. **Status filter unanswered**
   - Preconditions: Same as above.
   - Steps: status=Unanswered, Apply.
   - Expected: Only unanswered questions shown.

13. **Date preset last 7 days**
   - Preconditions: Questions older and newer than 7 days.
   - Steps: Click Last 7 days.
   - Expected: Only questions created in last 7 days.

14. **Custom date range**
   - Preconditions: Questions across range.
   - Steps: Set From / To, Apply.
   - Expected: Results fall within range; preset chip shows range.

15. **Clear all filters**
   - Preconditions: Filters active.
   - Steps: Click Clear all.
   - Expected: Filter inputs reset; chip bar cleared; full list returns.

16. **Search matches title/body**
   - Preconditions: Question with unique keyword in title/body.
   - Steps: Search keyword.
   - Expected: Question appears; relevance ordering highlights match.

17. **Search matches answer body**
   - Preconditions: Answer contains unique keyword; parent question exists.
   - Steps: Search keyword.
   - Expected: Parent question appears even if keyword not in question text.

18. **Empty search behaves as list**
   - Preconditions: Multiple questions.
   - Steps: Submit search with empty string, Apply.
   - Expected: Standard listing/pagination.

19. **View question shows category/tags**
   - Preconditions: Question classified.
   - Steps: Open show page.
   - Expected: Category chip and tag chips visible near title.

20. **Cannot access admin taxonomy pages**
   - Preconditions: Logged in as member.
   - Steps: Visit `/admin/categories` or `/admin/tags`.
   - Expected: 403.

## Moderator
21. **Classify when editing any question**
   - Preconditions: Moderator logged in; question authored by another user.
   - Steps: Edit question, set category/tags, save.
   - Expected: Changes saved; show page updated.

22. **Cannot manage taxonomy**
   - Preconditions: Moderator logged in.
   - Steps: Visit `/admin/categories`.
   - Expected: 403 (admin-only).

23. **Search with filters combined**
   - Preconditions: Questions with overlapping tags/categories.
   - Steps: Apply search text + category + tags + status.
   - Expected: Results honor all filters (intersection behavior).

## Admin
24. **Create category**
   - Preconditions: Admin logged in.
   - Steps: Admin > Categories > New; fill name/description; optional parent; save.
   - Expected: Category saved; slug auto-generated; appears in list.

25. **Edit category**
   - Preconditions: Category exists.
   - Steps: Edit name/description/parent; save.
   - Expected: Updates persist; slug refreshed for new name.

26. **Delete category with children blocked**
   - Preconditions: Parent category with child.
   - Steps: Attempt to delete parent.
   - Expected: Operation rejected with clear message; parent remains.

27. **Delete category without children**
   - Preconditions: Leaf category (no children).
   - Steps: Delete.
   - Expected: Category removed; any linked questions set category=null.

28. **Create tag**
   - Preconditions: Admin logged in.
   - Steps: Admin > Tags > New; enter name; save.
   - Expected: Tag created; slug auto-generated; visible in list.

29. **Edit tag**
   - Preconditions: Tag exists.
   - Steps: Edit name; save.
   - Expected: Tag updated; slug refreshed.

30. **Delete tag detaches from questions**
   - Preconditions: Tag attached to one or more questions.
   - Steps: Delete tag.
   - Expected: Tag removed; pivot rows deleted; questions still load.

31. **Tags list search**
   - Preconditions: Multiple tags.
   - Steps: Use search box with partial name; submit.
   - Expected: List filtered server-side; query param retained.

32. **Admin classification on create**
   - Preconditions: Admin logged in.
   - Steps: Create question, set category/tags, submit.
   - Expected: Saved with chosen taxonomy; visible on show and index chips.

33. **Relevance ordering**
   - Preconditions: Two questions, one with keyword in title, another only in body.
   - Steps: Search keyword.
   - Expected: Title match ranks higher (weighted A > B).

34. **Date preset clears manual range**
   - Preconditions: Manual From/To set.
   - Steps: Click preset (e.g., Last 30 days).
   - Expected: From/To cleared; preset applied; URL shows `date_preset` only.

35. **Validation errors for empty taxonomy names**
   - Preconditions: Admin logged in.
   - Steps: Submit category/tag form with empty name.
   - Expected: Validation error displayed; no record created.
