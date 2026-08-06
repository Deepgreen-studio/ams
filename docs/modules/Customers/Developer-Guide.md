# Customer Management — Developer Guide

## Extending the domain

Follow existing DDD boundaries under `backend/app/Domains/Customers/`.

```
Controller → FormRequest + Policy authorize → Service → Repository → Model
                                                                      ↓
                                                         Domain Event → Listeners
```

Never put business rules in controllers. Prefer enums for statuses/types.

### Adding a nested resource

1. Migration + Model + Factory + Seeder impact (permissions if new)
2. Repository + Service + Form Requests + Resource
3. Controller methods + routes in `Routes/api.php`
4. Policy methods on `CustomerPolicy` (or split policy if surface grows)
5. Events + `LogCustomer*Activity` + optional `Prepare*Notifications`
6. Register listeners/policy in `AppServiceProvider`
7. Feature tests under `backend/tests/Feature/Customers/`
8. Frontend: service → Pinia store → pages/components → router tile

### Billing gateway

```
App\Domains\Customers\Contracts\SubscriptionBillingGatewayInterface
  ├── Billing\ManualBillingGateway   (default)
  └── Billing\StripeBillingGateway   (throws until implemented)
```

Configured via `config/billing.php` / `BILLING_PROVIDER`. Inject the interface into `SubscriptionService`.

### Document storage

```php
config('filesystems.customer_documents_disk')
// env: FILESYSTEM_CUSTOMER_DOCUMENTS_DISK (prefer private S3 in production)
```

Path convention: `customer-documents/{customerUuid}/{category}/{uuid}.{ext}`

### Analytics scores

`CustomerAnalyticsService::collectMetrics` + `scoreCustomer` produce daily `customer_analytics_snapshots`.

- History backfill capped at **14 days** (`ensureHistory`)
- Support tickets are **proxied** until Support domain exists — update `metrics.sources` when real tickets land
- Prefer scheduled job for nightly snapshot compute when volume grows (Jobs folder currently empty)

### Permissions seed

`CustomerPermission` constants must stay in sync with `RolesAndPermissionsSeeder` / `PermissionModule`.

## Frontend conventions

```
frontend/src/modules/customers/
  pages/          # route targets
  components/     # tables, forms, badges
  stores/         # Pinia
  services/       # Axios wrappers → /api/v1
```

Routes nested under `customers/:id/...` in `frontend/src/router/index.js`. Use shared `PageHeader`, `EmptyState`, `Pagination`, `DeleteConfirmation`.

Shared API client: `@/services/api` (Bearer + CSRF). Do not call `fetch` ad hoc.

## Testing

```bash
cd backend
php artisan test --filter=Customers
php artisan test --filter=CustomerAnalytics
```

Write Feature tests that:

- Assert guest `401`
- Assert happy-path JSON shape
- Assert permission denials for roles without `customers.*`
- Cover soft delete/restore and critical invariants (unique email per company, one primary, no cross-company assignment)

Unit tests for pure scoring/helpers should live under `tests/Unit/Customers/` (gap as of Phase 4.8).

## Known stubs

| Item | Action when ready |
|------|-------------------|
| StripeBillingGateway | Implement Stripe API + webhooks |
| Prepare*Notifications listeners | Queue mail/push notifications |
| Support ticket metrics | Point to Support domain tables |
| Jobs | Nightly analytics snapshot command/job |
