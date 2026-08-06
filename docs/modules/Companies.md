# Companies Module

## Overview

Enterprise Company & Organization Management for AMS Phase 1.5.

Supports single-company and multi-company operation, with architecture ready for future Multi-Tenant SaaS.

## Responsibilities

- Company CRUD with soft delete and restore
- Organization profile, branding, logo, and favicon
- Locale settings (timezone, language, currency, date/time formats)
- Departments, teams, and office locations
- Search, filter, sort, and pagination
- Spatie Activity Log auditing

## Folder Structure

```
backend/app/Domains/Companies/
  Controllers/
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

frontend/src/modules/companies/
  components/
  pages/
  services/
  stores/
```

## Database Tables

- `companies`
- `departments`
- `teams`
- `company_locations`
- `company_user` (multi-company membership, architecture-ready)

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/companies` | List companies |
| POST | `/api/v1/companies` | Create company |
| GET | `/api/v1/companies/{id}` | Show company |
| PUT | `/api/v1/companies/{id}` | Update company |
| DELETE | `/api/v1/companies/{id}` | Soft delete |
| POST | `/api/v1/companies/{id}/restore` | Restore |
| POST | `/api/v1/companies/{id}/logo` | Upload logo |
| POST | `/api/v1/companies/{id}/favicon` | Upload favicon |
| PUT | `/api/v1/companies/{id}/branding` | Update branding |
| GET/POST/PUT/DELETE | `/api/v1/departments` | Department CRUD |
| GET/POST/PUT/DELETE | `/api/v1/teams` | Team CRUD |
| GET/POST/PUT/DELETE | `/api/v1/company-locations` | Location CRUD |

## Permissions

- `companies.view`
- `companies.create`
- `companies.update`
- `companies.delete`
- `companies.restore`
- `companies.manage`

## Events

- CompanyCreated, CompanyUpdated, CompanyDeleted
- DepartmentCreated, DepartmentUpdated
- TeamCreated, LocationCreated
- BrandingUpdated

## Testing Notes

```bash
php artisan test --filter=CompanyManagementTest
```

Covers auth gates, CRUD, validation, org units, logo upload, and branding.
