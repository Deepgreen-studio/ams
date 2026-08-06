# Applications Module

Enterprise Application Management for AMS (Phases 3.1–3.8).

## Documentation set

| Document | Description |
|----------|-------------|
| [Overview](./Applications/Overview.md) | Module purpose, architecture, permissions |
| [Database](./Applications/Database.md) | Tables, relationships, encryption, indexes |
| [API](./Applications/API.md) | Endpoint inventory by submodule |
| [Releases](./Applications/Releases.md) | Release lifecycle & constraints |
| [Review Reports](./Applications/Review-Reports.md) | Architecture, security, performance, testing, readiness |

## Phase summary

| Phase | Deliverable |
|-------|-------------|
| 3.1 | Application foundation (CRUD) |
| 3.2 | Version management |
| 3.3 | Environment management |
| 3.4 | Dynamic configuration |
| 3.5 | Release management |
| 3.6 | Crash & health monitoring |
| 3.7 | Application analytics |
| 3.8 | Module review + documentation (this milestone) |

## Quick test

```bash
cd backend
php artisan migrate
php artisan test --filter=Application
```

## Frontend entry

`/applications` → application detail subnav: Versions | Environments | Configurations | Releases | Monitoring | Analytics

## Permissions

`applications.view` · `applications.create` · `applications.update` · `applications.delete`
