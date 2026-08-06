# Customer Management — API Documentation

**Base path:** `/api/v1`  
**Auth:** Laravel Sanctum (`auth:sanctum`)  
**Throttle:** `throttle:api`  
**Permissions:** Spatie middleware on each route + `authorize()` in controllers  

## Response envelope

```json
{ "success": true, "message": "", "data": {} }
```

Validation failures return HTTP 422 with `errors`. Unauthorized: 401 / 403.

Path parameters use **UUID** (`getRouteKeyName`).

---

## Customers — `/customers`

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/customers` | view | List (search, filters, sort, pagination) |
| GET | `/customers/statistics` | view | Aggregate stats |
| POST | `/customers` | create | Create |
| GET | `/customers/{customer}` | view | Show |
| PUT | `/customers/{customer}` | update | Update |
| DELETE | `/customers/{customer}` | delete | Soft delete |
| POST | `/customers/{customer}/restore` | restore\|delete | Restore |

---

## Contacts — `/customer-contacts`

| Method | Path | Permission |
|--------|------|------------|
| GET | `/` | view |
| POST | `/` | update\|create |
| GET | `/{contact}` | view |
| PUT | `/{contact}` | update |
| DELETE | `/{contact}` | delete\|update |
| POST | `/{contact}/restore` | restore\|delete\|update |
| GET | `/{contact}/timeline` | view |

Query typically includes `customer={uuid}`.

---

## Application assignments — `/customer-applications`

CRUD + restore + `GET /history` + `GET /{assignment}/timeline`. Store requires update\|create.

---

## Subscriptions — `/customer-subscriptions`

| Extra | Path | Permission |
|-------|------|------------|
| Dashboard | GET `/dashboard` | view |
| Statistics | GET `/statistics` | view |
| Cancel | POST `/{subscription}/cancel` | update |
| Timeline | GET `/{subscription}/timeline` | view |

Plus standard list/create/show/update/delete/restore.

---

## Licenses — `/customer-licenses`

| Extra | Path | Permission |
|-------|------|------------|
| History | GET `/history` | view |
| Revoke | POST `/{license}/revoke` | update |
| Timeline | GET `/{license}/timeline` | view |

Plus standard CRUD + restore.

---

## Documents — `/customer-documents`

| Extra | Path | Permission |
|-------|------|------------|
| Folders | GET `/folders` | view |
| Statistics | GET `/statistics` | view |
| New version | POST `/{document}/versions` | update |
| Version list | GET `/{document}/versions` | view |
| Download | GET `/{document}/download` | view |
| Preview | GET `/{document}/preview` | view |
| Timeline | GET `/{document}/timeline` | view |

Plus list/store/show/update/delete/restore.

---

## Communication center

### Hub — `/customer-communication-center`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/overview` | Notes/tasks/comms summary + reminders |
| GET | `/timeline` | Merged activity timeline |
| GET | `/activity` | Recent activity |
| GET | `/calendar` | Calendar feed |

### Notes — `/customer-notes`

CRUD + restore.

### Tasks — `/customer-tasks`

CRUD + restore + `GET /calendar` + `POST /{task}/complete`.

### Communications — `/customer-communications`

CRUD + restore (email | call | meeting).

---

## Analytics — `/customer-analytics`

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/dashboard?customer=&from=&to=` | view | Full dashboard payload |
| GET | `/health?customer=` | view | Health/activity/risk |
| GET | `/trends?customer=&from=&to=` | view | Charts + growth |
| GET | `/usage?customer=` | view | Usage report |
| POST | `/refresh` body `{customer}` | update\|create | Force snapshot recompute |

### Dashboard `data` shape (high level)

- `customer`, `current` (scores + counters), `risk_indicators[]`
- `usage_report`, `charts` (labels + series arrays), `growth`, `timeline[]`
- `from`, `to`

### Metric sources (documented in snapshot `metrics.sources`)

| Metric | Source |
|--------|--------|
| API usage | Assigned apps’ `application_analytics_daily.sessions` |
| Login activity | Spatie activity on customer-domain subjects |
| Support tickets | **Proxy:** open tasks + email/call communications |

---

## Events (audit)

Major create/update/delete/restore/cancel/revoke/upload/complete/refresh actions dispatch domain events logged via Spatie Activity Log.
