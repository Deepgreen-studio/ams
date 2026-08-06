# Customer Management — Module Overview

**Phase:** 4.1–4.8 (Foundation through Review)  
**Status:** Feature-complete for Phase 4; gaps documented in readiness report  
**Stack:** Laravel 12 API + Vue 3 admin UI  

## Purpose

Enterprise customer lifecycle management from a single AMS dashboard: profiles, contacts, application assignments, subscriptions and licenses, documents, communication history, and health analytics.

## Submodules

| Submodule | Responsibility |
|-----------|----------------|
| Customers | CRUD, company scoping fields, type/status, soft delete/restore, statistics |
| Contacts | Primary/technical/billing/support/emergency contacts, timeline |
| Applications | Assign applications + environments + integrations, ownership, history |
| Subscriptions | Plans (trial/monthly/yearly/lifetime/enterprise), renewals, cancel, billing gateway |
| Licenses | Keys, activations, revoke, history |
| Documents | Category folders, upload, versioning, preview/download |
| Communications | Notes, tasks/reminders, email/call/meeting logs, calendar, hub timeline |
| Analytics | Daily snapshots, health/activity scores, risk, charts, usage, growth |

## Domain Architecture

```
HTTP (Sanctum + permission)
  → Controllers (authorize, validate, call service, JSON response)
    → Services (business rules, transactions, events)
      → Repositories (query/persistence)
        → Models (Eloquent, casts, relations)
```

Namespace: `App\Domains\Customers\`

Backend layout: Controllers, Requests, Services, Repositories, Models, Enums, Resources, Events, Listeners, Policies, Routes, Contracts, DTOs, Billing.

Frontend layout: `frontend/src/modules/customers/{pages,components,stores,services}`

## Permissions

| Permission | Usage |
|------------|--------|
| `customers.view` | Read lists, details, analytics, timelines |
| `customers.create` | Create customers (and nested manage where OR’d with update) |
| `customers.update` | Mutate nested resources, cancel/revoke, refresh analytics |
| `customers.delete` | Soft delete |
| `customers.restore` | Restore soft-deleted records |

Policy: `CustomerPolicy` (permission-based; no company isolation yet). Shared across all nested customer entities.

## Cross-Cutting Concerns

- UUID route keys on domain models
- Soft deletes on mutable entities (analytics snapshots excluded)
- Spatie Activity Log via domain events + `LogCustomer*Activity` listeners
- Notification preparation listeners stubbed (`Prepare*Notifications`)
- Billing via `SubscriptionBillingGatewayInterface` (Manual default; Stripe stub)
- Standard API envelope: `{ success, message, data }`

## Related Docs

- [Database Documentation](./Database.md)
- [API Documentation](./API.md)
- [User Guide](./User-Guide.md)
- [Developer Guide](./Developer-Guide.md)
- [Phase 4 Review Reports](./Review-Reports.md)
- [Module index](../Customers.md)
