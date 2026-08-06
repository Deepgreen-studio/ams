# Compliance API

Base path: `/api/v1/compliance`  
Auth: `auth:sanctum` + Spatie `permission:compliance.*`  
Envelope: `{ "success": true|false, "message": "", "data": {} }`

## Submodules (summary)

| Prefix | Purpose |
|--------|---------|
| `/dashboard`, `/cases` | Compliance cases |
| `/privacy-requests` | DSAR / privacy requests |
| `/consents` | Consent types, records, preferences |
| `/breaches` | Data breach lifecycle |
| `/dpia` | DPIA assessments + risk register |
| `/policies` | Policy documents, versions, approvals |
| `/analytics` | Cross-module analytics & export |

~99 registered routes under `compliance` (Phase 7.8 inventory).

## Analytics (Phase 7.7)

| Method | Path | Permission |
|--------|------|------------|
| GET | `/analytics/dashboard` | view |
| GET | `/analytics/risks` | view |
| GET | `/analytics/reports/gdpr` | view |
| GET | `/analytics/reports/consent` | view |
| GET | `/analytics/reports/audit` | view |
| GET | `/analytics/export?format=csv\|excel\|pdf&report=overview\|gdpr\|consent\|audit\|risks` | view |

Query filters (where applicable): `company`, `from`, `to`.

## Policies (Phase 7.6)

| Method | Path |
|--------|------|
| GET | `/policies/dashboard` |
| GET/POST | `/policies` |
| GET/PUT/DELETE | `/policies/{policy}` |
| GET | `/policies/{policy}/versions` |
| GET | `/policies/{policy}/versions/compare?from=&to=` |
| POST | `/policies/{policy}/versions/{version}/restore` |
| POST | `/policies/{policy}/submit` |
| POST | `/policies/{policy}/publish` |
| GET | `/policies/approvals` |
| POST | `/policies/approvals/{approval}/approve\|reject` |
| GET/POST | `/policies/{policy}/cms-versions`, `/link-cms` |

## Common patterns

- Identifiers: UUID (preferred) or numeric id where resolvers support both.  
- Soft-deleted resources: restore endpoints on cases/privacy where implemented.  
- Workflow actions are POST verbs (`submit`, `approve`, `publish`, `contain`, …).

## Error codes

| HTTP | Meaning |
|------|---------|
| 401 | Unauthenticated |
| 403 | Missing permission / Gate deny |
| 404 | Resource not found |
| 422 | Validation or workflow rule failure |

Full per-endpoint contracts live in Form Requests + Feature tests under `tests/Feature/Compliance/`.
