# Integration Hub — Documentation Index

**Milestone:** Phase 2.8 — Integration Hub Review  
**Date:** 2026-08-03  
**Status:** Review complete — awaiting approval before Phase 3

## Purpose

The Integration Hub is the enterprise foundation for connecting AMS to unlimited external APIs, webhooks, synchronized data flows, field mapping, async processing, and operational health monitoring.

## Documentation Map

| Document | Description |
|----------|-------------|
| [API Documentation](./API.md) | REST endpoints for integrations, sync, mappings, queue, monitoring |
| [Webhook Documentation](./Webhooks.md) | Incoming/outgoing webhooks, signatures, retries, events |
| [Connect Any App → Support](./Connect-Any-App-Support.md) | App → AMS Support SMS ingest + AMS → app Public reply / SMS out contract |
| [Connect Website → Support + Compliance](./Connect-Website-Support-Compliance.md) | Any website → Support / Complaint / Privacy auto-route + copy-paste examples |
| [Example payloads](./examples/README.md) | Ready JSON bodies for help, complaint, privacy, SMS |
| [Developer Guide](./Developer-Guide.md) | How to extend the hub from other domains safely |
| [Architecture Report](./reports/Architecture-Report.md) | Layering, DDD boundaries, engine ownership |
| [Security Report](./reports/Security-Report.md) | AuthZ, crypto, SSRF, findings |
| [Performance Report](./reports/Performance-Report.md) | Queues, rate limits, caching, indexes |
| [Integration Report](./reports/Integration-Report.md) | Engine coverage and readiness by capability |
| [Testing Report](./reports/Testing-Report.md) | Suite results (41 passed) |
| [Production Readiness Report](./reports/Production-Readiness-Report.md) | Go / no-go with scored checklist |

## Related Module Docs

- `docs/modules/Integrations.md`
- `docs/modules/Webhooks.md`
- `docs/modules/Sync.md`
- `docs/modules/DataMappings.md`
- `docs/modules/Queue.md`
- `docs/modules/Monitoring.md`

## Capability Overview

| Phase | Capability | Shared Engine | Domain Surface |
|-------|------------|---------------|----------------|
| 2.1 | Integration registry | — | `Domains/Integrations` |
| 2.2 | API Connection Engine | `Shared/Services/Http` | Connection API + UI |
| 2.3 | Webhook Engine | `Shared/Services/Webhook` | Webhooks API + UI |
| 2.4 | Sync Engine | `Shared/Services/Sync` | Sync API + UI |
| 2.5 | Data Mapping | `Shared/Services/Mapping` | Mappings API + UI |
| 2.6 | Queue Processing | `Shared/Services/Queue` | `Domains/Queue` |
| 2.7 | Monitoring | `Shared/Services/Monitoring` | `Domains/Monitoring` |
| 2.8 | Review & docs | — | this folder |

## Non-Negotiable Rules

1. Outbound HTTP **only** via `ApiClientService`.
2. Webhook fan-out via `WebhookService::dispatchEvent()` / `WebhookEngine`.
3. Sync via `IntegrationSyncService` / `SyncService`.
4. Mapping via `DataMappingService::transformWithProfile()` / `MappingEngine`.
5. Jobs tracked via AMS queue config + `TrackQueuedJob` middleware.
6. Controllers validate/authorize/delegate — no business logic in controllers.

## Runtime Prerequisites

```bash
# Workers (priority order)
php artisan queue:work --queue=high,imports,exports,webhooks,syncs,notifications,default,low

# Scheduler (required for sync + monitoring capture)
php artisan schedule:work
```

Permissions are seeded via `RolesAndPermissionsSeeder` (`integrations.*`, `queue.*`, `monitoring.*`).
