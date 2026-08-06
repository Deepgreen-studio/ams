# Users Module

## Overview

Enterprise User Management for AMS Phase 1.3.

## Responsibilities

- User CRUD with soft delete, restore, and force delete
- Authenticated profile and avatar management
- Search, filter, sort, and pagination
- Activity summary via Spatie Activity Log
- Architecture-ready login history storage

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

- `users` (extended enterprise fields)
- `user_login_histories` (architecture ready)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List users |
| POST | `/api/v1/users` | Create user |
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
