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
