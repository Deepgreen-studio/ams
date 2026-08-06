# Authentication

Enterprise authentication for AMS Phase 1.2+.

## Stack

- Laravel Sanctum (SPA cookie + personal access tokens)
- Rate-limited login/password endpoints
- Soft-deactivated users cannot authenticate

## Capabilities

- Login / Logout / Logout all devices
- Session me + token refresh
- Forgot / Reset password
- Change password
- Email verification + resend
- Architecture stubs for 2FA, device management, SSO

## Key Paths

- Domain: `backend/app/Domains/Authentication/`
- Frontend: `frontend/src/modules/authentication/`
- Feature tests: `backend/tests/Feature/Authentication/AuthenticationTest.php`

## Endpoints

Base: `/api/v1/auth/*` and `GET /sanctum/csrf-cookie`

See `docs/API.md` for the full table.

## Security Notes

- Strong default password rules via `Password::defaults()`
- Login throttles: 5/min per email+IP
- Never reveal whether an email exists during password reset
- Default seeded admin credentials are for **local development only**
