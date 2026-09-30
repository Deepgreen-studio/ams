# Roles & Permissions Module

## Overview

Enterprise RBAC for AMS Phase 1.4 using Spatie Laravel Permission, Policies, Gates, and permission middleware.

## Default Roles

- Super Admin
- Company Admin
- Manager
- Developer
- QA Tester
- Support Manager
- Support Agent
- Content Manager
- Compliance Officer
- Customer
- Read Only User

## Roles Permissions

| Permission | Screen action |
|------------|----------------|
| `roles.view` | View a role |
| `roles.create` | Create Role |
| `roles.update` | Edit |
| `roles.assign` | Permissions |
| `roles.assign-roles` | Assign Roles |
| `roles.matrix` | Permission Matrix |
| `roles.view-trash` | Soft Delete view |
| `roles.delete` | Soft delete a role |
| `roles.restore` | Restore |
| `roles.force-delete` | Permanent delete |

`users.assign-roles` still grants Assign Roles. Company Admin receives the roles permissions above.

## Permission Groups

Authentication, Dashboard, Users, Roles, Companies, Applications, Customers, Integrations, Releases, Content, Support, Notifications, Analytics, Compliance, Reports, Settings.

## API Endpoints

| Method | Endpoint |
|--------|----------|
| GET/POST | `/api/v1/roles` |
| GET/PUT/DELETE | `/api/v1/roles/{id}` |
| POST | `/api/v1/roles/{id}/restore` |
| POST | `/api/v1/roles/{id}/permissions` |
| GET | `/api/v1/permissions` |
| GET | `/api/v1/permissions/groups` |
| GET | `/api/v1/permissions/matrix` |
| POST | `/api/v1/users/{id}/roles` |
| DELETE | `/api/v1/users/{id}/roles/{role}` |

## Testing

```bash
cd backend
php artisan test --filter=RoleManagementTest
```
