# Development Guide

## Prerequisites

- PHP 8.3+
- Composer 2
- Node.js 20+ / npm
- MySQL 8+
- Redis
- Optional: Docker Desktop for infra services

## First-Time Setup

### Windows (Herd / PowerShell)

```powershell
./scripts/setup.ps1
```

### Unix

```bash
chmod +x ./scripts/setup.sh
./scripts/setup.sh
```

Or follow the README manual steps.

## Daily Workflow

1. Start infra: `docker compose up -d`
2. Backend API: `cd backend && php artisan serve` (or Herd site / `php -S`)
3. Frontend: `cd frontend && npm run dev`
4. Work **one milestone at a time** (see `.cursorrules`)
5. Write/adjust Feature tests with the change
6. Run `php artisan test` and Pint before handoff

## Coding Rules (Summary)

- Controllers: validate → authorize → call service → JSON response
- Business logic only in Services
- DB access via Repositories
- REST under `/api/v1`
- Permissions via Spatie middleware + Policies
- Frontend: Vue Composition API + Pinia + module folders

Full standards: `docs/CodingStandards.md`

## Testing

```bash
cd backend
php artisan test
php artisan test --filter=AuthenticationTest
php artisan test --filter=UserManagementTest
php artisan test --filter=RoleManagementTest
php artisan test --filter=CompanyManagementTest
php artisan test --filter=SettingsManagementTest
php artisan test --filter=AuditMonitoringTest
```

## Formatting

```bash
cd backend
vendor/bin/pint
```

## Queues & Scheduler (local)

```bash
php artisan queue:work
php artisan schedule:work
```

Configure `QUEUE_CONNECTION=redis` (or `database`) in `.env`.
