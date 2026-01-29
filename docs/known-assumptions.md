# Known Assumptions (Phase B)

- Roles are stored as a validated string column on the `users` table (no roles pivot table).
- Admin access is required for `/admin/*`; moderator access is required for `/moderator/*`. Admins do not automatically access the moderator area in this phase.
- User deletion is intentionally omitted in Phase B; only role updates are supported.
- Demo credentials use the password `password` and are intended for local development only.
