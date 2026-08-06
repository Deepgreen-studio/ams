# API

Base path: `/api/v1`

## Response Contract

Success:

```json
{ "success": true, "message": "", "data": {} }
```

Validation error:

```json
{ "success": false, "message": "Validation Failed", "errors": {} }
```

Server error:

```json
{ "success": false, "message": "Unexpected Error" }
```

## Authentication Endpoints

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | `/api/v1/auth/login` | Public (throttled) |
| POST | `/api/v1/auth/logout` | Sanctum |
| POST | `/api/v1/auth/logout-all` | Sanctum |
| GET | `/api/v1/auth/me` | Sanctum |
| POST | `/api/v1/auth/refresh` | Sanctum |
| POST | `/api/v1/auth/forgot-password` | Public (throttled) |
| POST | `/api/v1/auth/reset-password` | Public (throttled) |
| POST | `/api/v1/auth/change-password` | Sanctum |
| POST | `/api/v1/auth/email/verification-notification` | Sanctum |
| GET | `/api/v1/auth/verify-email/{id}/{hash}` | Signed URL |

Sanctum CSRF cookie: `GET /sanctum/csrf-cookie`

Login success payload includes `user` and `token` (Sanctum personal access token) for web and future mobile clients.

## Companies Endpoints

| Method | Endpoint | Auth / Permission |
|--------|----------|-------------------|
| GET | `/api/v1/companies` | Sanctum + `companies.view` |
| POST | `/api/v1/companies` | Sanctum + `companies.create` |
| GET | `/api/v1/companies/{id}` | Sanctum + `companies.view` |
| PUT | `/api/v1/companies/{id}` | Sanctum + `companies.update` |
| DELETE | `/api/v1/companies/{id}` | Sanctum + `companies.delete` |
| POST | `/api/v1/companies/{id}/restore` | Sanctum + `companies.restore` |
| POST | `/api/v1/companies/{id}/logo` | Sanctum + `companies.manage` |
| POST | `/api/v1/companies/{id}/favicon` | Sanctum + `companies.manage` |
| PUT | `/api/v1/companies/{id}/branding` | Sanctum + `companies.manage` |
| GET/POST/PUT/DELETE | `/api/v1/departments` | Sanctum + companies permissions |
| GET/POST/PUT/DELETE | `/api/v1/teams` | Sanctum + companies permissions |
| GET/POST/PUT/DELETE | `/api/v1/company-locations` | Sanctum + companies permissions |

See `docs/modules/Companies.md` for full module details.

## Settings & Media Endpoints

| Method | Endpoint | Auth / Permission |
|--------|----------|-------------------|
| GET/PUT | `/api/v1/settings` | Sanctum + settings.view/update |
| GET/PUT | `/api/v1/settings/{email\|storage\|security\|api\|queue}` | Sanctum + settings.view/update |
| GET | `/api/v1/settings/system-info` | Sanctum + settings.view |
| GET/POST/DELETE | `/api/v1/media` | Sanctum + settings.view / manage\|update |
| GET/POST/PUT/DELETE | `/api/v1/folders` | Sanctum + settings.view / manage\|update |

See `docs/modules/Settings.md` for full module details.

## Audit & Monitoring Endpoints

| Method | Endpoint | Auth / Permission |
|--------|----------|-------------------|
| GET | `/api/v1/activity-logs` | Sanctum + `audit.view` |
| GET | `/api/v1/activity-logs/export` | Sanctum + `audit.export`/`manage` |
| GET | `/api/v1/activity-logs/{id}` | Sanctum + `audit.view` |
| GET | `/api/v1/audit-logs` | Sanctum + `audit.view` |
| GET | `/api/v1/login-history` | Sanctum + `audit.view` |
| GET | `/api/v1/system-events` | Sanctum + `audit.view` |
| GET | `/api/v1/api-logs` | Sanctum + `audit.view` |
| GET | `/api/v1/error-logs` | Sanctum + `audit.view` |

See `docs/modules/Audit.md` for full module details.
