# Folder Structure

## Monorepo

```text
AMS/
├── backend/
│   ├── app/
│   │   ├── Domains/          # Business modules (DDD)
│   │   ├── Models/           # Shared User model
│   │   ├── Providers/
│   │   └── Shared/           # Cross-cutting responses, helpers, services
│   ├── config/
│   ├── database/migrations|seeders|factories
│   ├── routes/api.php + routes/api/v1.php
│   └── tests/Feature|Unit
├── frontend/
│   └── src/
│       ├── components/
│       ├── layouts/
│       ├── modules/          # Feature modules (auth, users, roles, companies, settings, audit)
│       ├── pages/
│       ├── router/
│       ├── services/
│       └── stores/
├── docs/
├── docker/
├── scripts/
└── docker-compose.yml
```

## Domain Skeleton

Each business domain under `backend/app/Domains/{Name}` follows:

```text
Controllers/ Requests/ Services/ Repositories/ Models/
Policies/ Resources/ Events/ Listeners/ Notifications/
Routes/ Tests/ Jobs/
```

## Frontend Module Skeleton

```text
frontend/src/modules/{name}/
  pages/
  components/
  stores/
  services/
```

## Implemented Domains (Phase 1)

Authentication · Users · Roles · Companies · Settings · Audit

Future domains are scaffold placeholders (Applications, Customers, Integrations, etc.).
