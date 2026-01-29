# Phase B End-to-End User Test Plan

## Guest
### 1) View public home
- Preconditions: None.
- Steps: Visit `/`.
- Expected Result: Home page loads with Phase B messaging and login/register CTAs.

### 2) Attempt to access admin panel
- Preconditions: None.
- Steps: Visit `/admin`.
- Expected Result: Redirected to `/login`.

### 3) Attempt to access moderator area
- Preconditions: None.
- Steps: Visit `/moderator`.
- Expected Result: Redirected to `/login`.

### 4) Register a new account
- Preconditions: None.
- Steps: Visit `/register`, fill name/email/password, submit.
- Expected Result: Account is created, user is logged in, redirected to `/dashboard`.

### 5) Login with invalid credentials
- Preconditions: None.
- Steps: Visit `/login`, enter invalid credentials, submit.
- Expected Result: Validation error shown, user remains on login page.

### 6) Login with valid credentials
- Preconditions: Valid user exists.
- Steps: Visit `/login`, submit valid credentials.
- Expected Result: Redirected to `/dashboard`.

## Member (Član)
### 7) Access member dashboard
- Preconditions: Logged in as `member@knowledge-hub.test`.
- Steps: Visit `/dashboard`.
- Expected Result: Dashboard loads and indicates member area.

### 8) Attempt to access admin panel
- Preconditions: Logged in as member.
- Steps: Visit `/admin`.
- Expected Result: 403 Forbidden.

### 9) Attempt to access moderator area
- Preconditions: Logged in as member.
- Steps: Visit `/moderator`.
- Expected Result: 403 Forbidden.

### 10) Check user menu
- Preconditions: Logged in as member.
- Steps: Open the user dropdown in the header.
- Expected Result: User name/email shown, logout available, no admin links.

## Moderator
### 11) Access moderator area
- Preconditions: Logged in as `moderator@knowledge-hub.test`.
- Steps: Visit `/moderator`.
- Expected Result: Moderator dashboard loads.

### 12) Attempt to access admin panel
- Preconditions: Logged in as moderator.
- Steps: Visit `/admin`.
- Expected Result: 403 Forbidden.

### 13) Verify role badge
- Preconditions: Logged in as moderator.
- Steps: Check the role badge in the header.
- Expected Result: Badge displays “Moderator”.

## Admin
### 14) Access admin dashboard
- Preconditions: Logged in as `admin@knowledge-hub.test`.
- Steps: Visit `/admin`.
- Expected Result: Admin dashboard loads.

### 15) Open users list
- Preconditions: Logged in as admin.
- Steps: Visit `/admin/users`.
- Expected Result: Users table loads with name, email, role, created date.

### 16) Search users by email
- Preconditions: Logged in as admin.
- Steps: On `/admin/users`, search for `member@knowledge-hub.test`.
- Expected Result: List filters to the matching user.

### 17) Update another user role
- Preconditions: Logged in as admin, target user exists.
- Steps: Edit a member user and change role to Moderator.
- Expected Result: Success message appears; role updates in list.

### 18) Prevent self-demotion when only admin
- Preconditions: Only one admin exists (the logged-in admin).
- Steps: Edit own role to Member and submit.
- Expected Result: Update is rejected with a validation error.

### 19) View placeholder pages
- Preconditions: Logged in as admin.
- Steps: Open `/admin/categories` and `/admin/tags`.
- Expected Result: Pages show “Coming in Phase E”.
