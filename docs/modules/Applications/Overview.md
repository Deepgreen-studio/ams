# Application Management — Module Overview

**Phase:** 3.1–3.8 (Foundation through Review)  
**Status:** Feature-complete for Phase 3; gaps documented in readiness report  
**Stack:** Laravel 12 API + Vue 3 admin UI  

## Purpose

Enterprise lifecycle management for mobile/web applications from a single AMS dashboard: portfolio, versions, environments, remote configuration, releases, crash/health monitoring, and product analytics.

## Submodules

| Submodule | Responsibility |
|-----------|----------------|
| Applications | CRUD, company scoping, platform/status/visibility, soft delete/restore |
| Versions | Semantic versioning, compare, timeline, history, production sync |
| Environments | Dev/Testing/Staging/Production/Sandbox, switch current, health check, encrypted variables |
| Configurations | Feature flags, remote config, maintenance, API/Firebase/Analytics keys, JSON validation & history |
| Releases | Plan, schedule, approve/reject, deploy, rollback, release notes |
| Monitoring | Crash/ANR/API errors, health score, device stats, charts, threshold alerts |
| Analytics | DAU/MAU, sessions, retention, installs/uninstalls, countries, devices/OS, heatmaps, trends |

## Domain Architecture

```
HTTP (Sanctum + permission)
  → Controllers (authorize, validate, call service, JSON response)
    → Services (business rules, transactions, events)
      → Repositories (query/persistence)
        → Models (Eloquent, casts, relations)
```

Namespace: `App\Domains\Applications\`

Backend layout: Controllers, Requests, Services, Repositories, Models, Enums, Resources, Events, Listeners, Policies, Routes, Tests.

Frontend layout: `frontend/src/modules/applications/{pages,components,stores,services}`

## Permissions

| Permission | Usage |
|------------|--------|
| `applications.view` | Read dashboards, lists, details |
| `applications.create` | Create applications |
| `applications.update` | Mutate nested resources, ingest endpoints |
| `applications.delete` | Soft delete / restore applications |

Policy: `ApplicationPolicy` (permission-based; no company isolation yet).

## Cross-Cutting Concerns

- UUID route keys on primary resources
- Soft deletes where appropriate
- Spatie Activity Log via domain events + `LogApplicationActivity`
- Notification hooks stubbed in `PrepareApplicationNotifications`
- Standard API envelope: `{ success, message, data }`

## Related Docs

- [Database Documentation](./Database.md)
- [API Documentation](./API.md)
- [Release Documentation](./Releases.md)
- [Phase 3 Review Reports](./Review-Reports.md)
- [Module index](../Applications.md)
