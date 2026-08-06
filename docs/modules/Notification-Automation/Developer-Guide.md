# Developer Guide — Notification & Automation Platform

## Domain map

| Domain | Path | Route prefix |
|--------|------|--------------|
| Notifications | `app/Domains/Notifications` | `/api/v1/notifications` |
| Automation | `app/Domains/Automation` | `/api/v1/automation` |
| Workflows | `app/Domains/Workflows` | `/api/v1/workflows` |
| Scheduler | `app/Domains/Scheduler` | `/api/v1/scheduler` |
| AI | `app/Domains/Ai` | `/api/v1/ai` |
| Analytics | `app/Domains/Analytics` | `/api/v1/analytics` |

Frontend modules live under `frontend/src/modules/{notifications,automation,workflows,scheduler,ai,analytics}/`.

## Design rules

1. Controllers: validate → authorize → service → `ApiResponse` (no business SQL).
2. Services own transactions, events, and orchestration.
3. Repositories own queries; prefer filters + pagination helpers.
4. Use enums, Form Requests, API Resources, Spatie permissions.
5. Register routes via `Domains/{Name}/Routes/api.php` (DomainServiceProvider).
6. Register policies/events in `AppServiceProvider`.
7. Never hardcode AI vendors — use `AiProviderManager` + `config/ai.php`.

## Scheduled commands

Defined in `routes/console.php` (every minute, without overlapping):

| Command | Purpose |
|---------|---------|
| `automation:process` | Delayed + cron automation rules |
| `workflows:process-timeouts` | Escalate overdue workflow instances |
| `scheduler:process` | Due platform scheduled jobs |

Local Herd/production must run `php artisan schedule:work` (or cron `schedule:run`) **and** a queue worker.

## Extending automation

1. Add event key / listener mapping if new domain event.
2. Add action enum + executor branch in `AutomationActionExecutor`.
3. Gate dangerous actions behind stronger permissions when possible.
4. Feature test under `tests/Feature/Automation/`.
5. Update `docs/automation/Overview.md`.

## Extending AI

1. Implement `AiProviderInterface`.
2. Register in `config/ai.php` → `drivers`.
3. Add `AiProviderDriver` enum case if user-selectable.
4. Cover with Null-driver feature tests; never require live API keys in CI.

## Extending analytics

1. Add aggregation methods to `PlatformAnalyticsRepository`.
2. Expose via `PlatformAnalyticsService` + `AnalyticsController`.
3. Add SPA page under `modules/analytics` and export report key.
4. Prefer live SQL first; introduce snapshot tables only if needed.

## Frontend conventions

- Pinia store + Axios service per module
- Subnav components for module sections
- Reuse `PageHeader`, `SimpleLineChart`, `SimpleBarChart`
- Export blobs for CSV/Excel; handle PDF `pdf_ready` 422

## Testing conventions

```bash
php artisan test tests/Feature/Notifications
php artisan test tests/Feature/Automation
php artisan test tests/Feature/Workflows
php artisan test tests/Feature/Scheduler
php artisan test tests/Feature/Ai
php artisan test tests/Feature/Analytics
```

Add Sanctum + `RolesAndPermissionsSeeder` in feature `setUp()`. Prefer Null AI provider and deterministic seed data.

## Known tech debt for developers

| Item | Priority |
|------|----------|
| Wire notification click API + resource fields | High |
| Repository interfaces (DIP) across domains | Medium |
| Enforce AI daily token limit in assistant path | High |
| Replace stub scheduler handler side-effects | High |
| Encrypt notification channel config secrets | Medium |
| Unit tests for evaluators/executors | Medium |
