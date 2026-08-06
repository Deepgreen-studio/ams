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
