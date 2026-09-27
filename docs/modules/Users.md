# Users Module

## Overview

Enterprise User Management for AMS Phase 1.3.

## Responsibilities

- User CRUD with soft delete, restore, and force delete
- Invitation onboarding: administrators do not set passwords
- Account status separate from invitation status
- One role per user, assigned only with `users.assign-roles`
- Company timezone and language inherited when omitted
- Authenticated profile, avatar, and TOTP multi-factor authentication
- Search, filter, sort, and pagination
- Activity summary, login history, and session revocation

## Folder Structure

```
backend/app/Domains/Users/
  Controllers/
  Contracts/
  Enums/
  Events/
  Listeners/
  Models/
  Notifications/
  Policies/
  Repositories/
  Requests/
  Resources/
  Routes/
  Services/

frontend/src/modules/users/
  components/
  pages/
  services/
  stores/
```

## Database Tables

- `users` (enterprise fields, invitation status, encrypted TOTP secret)
- `user_login_histories`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List users |
| POST | `/api/v1/users` | Create user and send a one-time invitation |
| POST | `/api/v1/users/{id}/invitation` | Resend invitation |
| GET | `/api/v1/users/{id}/sessions` | List active sessions |
| DELETE | `/api/v1/users/{id}/sessions/{session}` | Revoke a session |
| POST | `/api/v1/users/profile/two-factor` | Begin MFA enrollment |
| POST | `/api/v1/users/profile/two-factor/confirm` | Confirm MFA |
| DELETE | `/api/v1/users/profile/two-factor` | Disable MFA |
| GET | `/api/v1/users/{id}` | Show user |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Soft delete |
| POST | `/api/v1/users/{id}/restore` | Restore |
| DELETE | `/api/v1/users/{id}/force-delete` | Permanent delete |
| GET | `/api/v1/users/profile` | Current profile |
| PUT | `/api/v1/users/profile` | Update profile |
| POST | `/api/v1/users/avatar` | Upload avatar |

## Permissions

- `users.view`
- `users.create`
- `users.update`
- `users.delete`
- `users.restore`
- `users.force-delete` (super-admin)
- `users.assign-roles` (required to send `roles` on create/update)

## Events

- `UserCreated`
- `UserUpdated`
- `UserDeleted`
- `UserRestored`
- `AvatarUpdated`

## Testing

```bash
cd backend
php artisan test --filter=UserManagementTest
```
