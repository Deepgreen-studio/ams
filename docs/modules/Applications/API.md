# Application Management — API Documentation

**Base URL:** `/api/v1`  
**Auth:** Laravel Sanctum (`auth:sanctum`)  
**Throttle:** `throttle:api`  
**Response:** `{ "success": bool, "message": string, "data": mixed }`

## Permissions

All routes require an authenticated user with the matching Spatie permission listed below.

## Applications

| Method | Path | Permission |
|--------|------|------------|
| GET | `/applications` | view |
| POST | `/applications` | create |
| GET | `/applications/{app}` | view |
| PUT | `/applications/{app}` | update |
| DELETE | `/applications/{app}` | delete |
| POST | `/applications/{app}/restore` | delete |

`{app}` accepts application UUID.

## Versions — `/applications/{app}/versions`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/` | view |
| POST | `/` | update |
| GET | `/compare` | view |
| GET | `/timeline` | view |
| GET | `/history` | view |
| GET | `/{version}` | view |
| PUT | `/{version}` | update |
| DELETE | `/{version}` | update |

## Environments — `/applications/{app}/environments`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/dashboard` | view |
| GET/POST | `/` | view / update |
| GET/PUT/DELETE | `/{environment}` | view / update |
| POST | `/{environment}/switch` | update |
| POST | `/{environment}/health-check` | update |

## Configurations — `/applications/{app}/configurations`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/catalog`, `/manager` | view |
| POST | `/validate` | view |
| GET/POST | `/` | view / update |
| GET/PUT/DELETE | `/{configuration}` | view / update |
| GET | `/{configuration}/history` | view |
| POST | `/{configuration}/history/{history}/restore` | update |
| POST | `/{configuration}/feature-flags` | update |
| POST | `/{configuration}/feature-flags/{flag}/toggle` | update |

## Releases — `/applications/{app}/releases`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/dashboard`, `/calendar`, `/timeline` | view |
| GET/POST | `/` | view / update |
| GET/PUT/DELETE | `/{release}` | view / update |
| POST | `/{release}/schedule` | update |
| POST | `/{release}/submit-approval` | update |
| POST | `/{release}/approve` | update |
| POST | `/{release}/reject` | update |
| POST | `/{release}/deploy` | update |
| POST | `/{release}/rollback` | update |

**Workflow:** plan → schedule → approve → deploy → optional rollback. Deploy requires `approval_status` ∈ {`approved`,`not_required`}.

## Monitoring — `/applications/{app}/monitoring`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/crash-dashboard`, `/health-dashboard`, `/charts`, `/device-statistics` | view |
| GET/POST | `/crashes` | view / update |
| GET/PUT/DELETE | `/crashes/{crash}` | view / update |
| POST | `/ingest/crash`, `/ingest/anr`, `/ingest/api-error`, `/ingest/health` | update |
| POST | `/health/refresh` | update |
| GET/POST | `/alerts` | view / update |
| PUT/DELETE | `/alerts/{alert}` | update |
| POST | `/alert-events/{event}/acknowledge` | update |

## Analytics — `/applications/{app}/analytics`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/dashboard`, `/trends`, `/heatmap`, `/countries`, `/devices` | view |
| POST | `/ingest` | update |

Ingest upserts the daily metrics row for `metric_date` and optionally replaces/updates countries, devices, and heatmap cells.

## Route inventory

Approximately **73** registered routes under `/api/v1/applications` (Phase 3 complete).

## Error semantics

| HTTP | Meaning |
|------|---------|
| 401 | Unauthenticated |
| 403 | Missing permission / policy denial |
| 404 | Resource not found for application scope |
| 422 | Validation or domain rule failure (`ApiException`) |
| 429 | Throttled |
