# Phase B: Authentication + RBAC

## Overview
Phase B adds authentication, role-based access control (RBAC), and the first admin skeleton with user management. The stack remains Laravel 12 + Inertia (Vue 3) + Tailwind.

## Authentication flow
- **Registration** creates a user with the default role of `member` (Član).
- **Login** redirects to the member dashboard (`/dashboard`).
- **Logout** returns to the public home page.
- Password reset and email verification routes are available through the Breeze scaffold (not surfaced as primary navigation).

## RBAC model
### Roles
- `admin`
  - Accesses `/admin` and manages users.
- `moderator`
  - Intended to edit/delete any content (policy scaffolding is in place).
- `member` (Član)
  - Intended to manage only their own content (policy scaffolding is in place).

### Storage choice (justification)
Roles are stored directly on the `users` table as a validated string column (`role`).  
This is the simplest robust option for Phase B: no join table, minimal overhead, and clear validation rules in one place. It also aligns with the current scope (only three roles).

### Authorization layer
Implemented in `App\Providers\AuthServiceProvider`:
- `access-admin` → admin-only access
- `moderate-content` → admin or moderator
- `manage-own-content` → admin, moderator, or owner ID match

Role checks for routes use the `EnsureRole` middleware, aliased as `role`.

## Route protection strategy
- `/admin/*` → `auth` + `role:admin`
- `/moderator/*` → `auth` + `role:moderator`
- `/dashboard` → `auth`
- Auth routes → Breeze default (`/login`, `/register`, `/logout`, etc.)

Inertia shared props include the authenticated user (id, name, email, role) and flash messages.

## Admin panel structure
- **Dashboard**: summary panel with quick navigation.
- **Users**: full implementation (search, pagination, role edit).
- **Categories**: placeholder page (Coming in Phase E).
- **Tags**: placeholder page (Coming in Phase E).

## Seeder users + credentials (dev-only)
The database seeder creates three demo users (idempotent). Use these for local testing only:

- **Admin**: `admin@knowledge-hub.test` / `password`
- **Moderator**: `moderator@knowledge-hub.test` / `password`
- **Member**: `member@knowledge-hub.test` / `password`

## Notes
- Self-demotion is blocked when the current admin is the only admin.
- User deletion is intentionally not implemented in Phase B.

---

## User Test Plan (End-to-End)

### Guest
**1) View public home** — Visit `/`. Expected: Home page loads with login/register CTAs.

**2) Attempt to access admin panel** — Visit `/admin`. Expected: Redirected to `/login`.

**3) Attempt to access moderator area** — Visit `/moderator`. Expected: Redirected to `/login`.

**4) Register a new account** — Visit `/register`, fill name/email/password, submit. Expected: Account created, logged in, redirected to `/dashboard`.

**5) Login with invalid credentials** — Visit `/login`, enter invalid credentials. Expected: Validation error, remain on login page.

**6) Login with valid credentials** — Visit `/login`, submit valid credentials. Expected: Redirected to `/dashboard`.

### Member (Član)
**7) Access member dashboard** — Logged in as `member@knowledge-hub.test`, visit `/dashboard`. Expected: Dashboard loads.

**8) Attempt to access admin panel** — Logged in as member, visit `/admin`. Expected: 403 Forbidden.

**9) Attempt to access moderator area** — Logged in as member, visit `/moderator`. Expected: 403 Forbidden.

**10) Check user menu** — Logged in as member, open user dropdown. Expected: Name/email and logout; no admin links.

### Moderator
**11) Access moderator area** — Logged in as `moderator@knowledge-hub.test`, visit `/moderator`. Expected: Moderator dashboard loads.

**12) Attempt to access admin panel** — Logged in as moderator, visit `/admin`. Expected: 403 Forbidden.

**13) Verify role badge** — Logged in as moderator; check header. Expected: Badge shows “Moderator”.

### Admin
**14) Access admin dashboard** — Logged in as `admin@knowledge-hub.test`, visit `/admin`. Expected: Admin dashboard loads.

**15) Open users list** — Logged in as admin, visit `/admin/users`. Expected: Users table with name, email, role, created date.

**16) Search users by email** — On `/admin/users`, search for `member@knowledge-hub.test`. Expected: List filters to matching user.

**17) Update another user role** — Edit a member, change role to Moderator. Expected: Success; role updates in list.

**18) Prevent self-demotion when only admin** — Only one admin exists; edit own role to Member and submit. Expected: Rejected with validation error.

**19) View placeholder pages** — Open `/admin/categories` and `/admin/tags`. Expected: “Coming in Phase E”.
