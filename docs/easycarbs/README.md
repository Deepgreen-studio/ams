# EasyCarbs — Imported Reference Data

Imported supplier materials for EasyCarbs. **Data import only** — no AMS functional or code changes.

## Files

| File | Contents |
|------|----------|
| [API-Documentation.md](./API-Documentation.md) | EasyCarb Backend API documentation (routes, auth, request/response shapes) |
| [DTAC-Security-Assessment-Request.md](./DTAC-Security-Assessment-Request.md) | DTAC / Security Assessment Request pack (app details, privacy, data flows, third parties, deployment) |

## AMS seeded records

Seeded via `php artisan db:seed --class=EasyCarbsCompanySeeder` (also included in `DatabaseSeeder`).

| Dashboard | What was seeded |
|-----------|-----------------|
| **Companies** | EasyCarbs |
| **Integrations** | EasyCarbs API (`easycarbs-api`) |
| **Applications** | EasyCarbs Android + iOS |
| **Environments** | Production + Staging per app |
| **Customers** | `mrdavid@gmail.com`, `admin@example.com` |
| **Mappings** | EasyCarbs Customers (4 field mappings) |

## Request routing (Support vs Compliance)

See [Support-Compliance-Workflow.md](./Support-Compliance-Workflow.md) for the full flowchart.

| Request | Involves personal data? | Module | Why |
|---------|-------------------------|--------|-----|
| Please remove my health information from my account. | Yes | Support intake → **auto-route to Compliance** privacy request | Health/PII; Support cannot fulfil → Compliance |
| I would like to temporarily disable my account. | No | **Support** only | Operational account suspension |

Seeded via Support tickets; health ticket auto-creates a linked Compliance privacy request.

Current demo records for `mrdavid@gmail.com`:

- Health: Support `SUP-20260804-00004` (pending, personal data) → Compliance `PRV-20260804-00003` (linked)
- Disable: Support `SUP-20260804-00005` (open, Support-only); customer **suspended**
## Notes

- Test account passwords from the source pack are **redacted** in repo docs.
- Screenshot binaries referenced in the DTAC appendix were not imported; descriptions only.
- Account-deletion path differs between packs (`POST /api/account/delete` vs `DELETE /api/settings/delete-account`); both are preserved as source data.
