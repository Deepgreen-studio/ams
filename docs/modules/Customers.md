# Customers Module

Enterprise Customer Relationship Management for AMS (Phases 4.1–4.8).

## Documentation set

| Document | Description |
|----------|-------------|
| [Overview](./Customers/Overview.md) | Module purpose, architecture, permissions |
| [Database](./Customers/Database.md) | Tables, relationships, indexes |
| [API](./Customers/API.md) | Endpoint inventory by submodule |
| [User Guide](./Customers/User-Guide.md) | Admin operator workflows |
| [Developer Guide](./Customers/Developer-Guide.md) | Extending the domain |
| [Review Reports](./Customers/Review-Reports.md) | Architecture, security, performance, testing, readiness |

## Phase summary

| Phase | Deliverable |
|-------|-------------|
| 4.1 | Customer foundation (CRUD) |
| 4.2 | Contacts |
| 4.3 | Application assignments |
| 4.4 | Subscriptions & licensing |
| 4.5 | Documents |
| 4.6 | Communication center |
| 4.7 | Customer analytics |
| 4.8 | Module review + documentation (this milestone) |

## Customer model

The owning **company** owns applications. A **customer** belongs to that company and is assigned to applications through an entitlement record (`customer_applications`). Customers are either an **individual** or an **organization**. Business and Enterprise are organization categories and share organization fields (organization name, legal name, registration/tax ID, primary contact).

Assignment ownership (`customer owned`, `platform managed`, `shared`) is operational responsibility. It does not transfer ownership of the application record.

`customer_number` is the immutable public id. `uuid` remains the internal identifier. `reference` is an optional business reference.

## Quick test

```bash
cd backend
php artisan migrate
php artisan test --filter=Customers
```

**Last review run (2026-08-03):** 41 feature tests passed (277 assertions).

## Frontend entry

`/customers` → customer hub tiles: Contacts | Applications | Subscriptions | Licenses | Documents | Communications | Analytics

## Permissions

`customers.view` · `customers.create` · `customers.update` · `customers.delete` · `customers.restore`
