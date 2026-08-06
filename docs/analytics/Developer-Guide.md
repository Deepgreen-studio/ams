# Developer Guide — Analytics Domain

## Location

```
backend/app/Domains/Analytics/
  Controllers/   Services/   Repositories/   Models/
  Requests/      Enums/      Policies/       Routes/api.php
frontend/src/modules/analytics/
  pages/  stores/  services/analyticsService.js  components/
```

## Architecture

```
HTTP → FormRequest → permission middleware → Controller
  → authorize(AnalyticsSubject)
  → Service (business logic)
  → Repository / cross-domain services
  → Model / MySQL
→ ApiResponse JSON
```

Do **not** put KPI logic in controllers. Prefer extending existing services (`BusinessAnalyticsService`, `SecurityAnalyticsService`, `ExecutiveAnalyticsService`) over new domains for portfolio analytics.

## Categories (`AnalyticsCategory`)

`business | operational | application | customer | api | system | security | executive`

Note: Application/API/System category tags exist for events/dashboards; dedicated portfolio boards for those live partly under Applications / Monitoring / Operational analytics.

## Key services

| Service | Phase | Role |
|---------|-------|------|
| `AnalyticsOverviewService` | 9.1 | Foundation KPIs |
| `AnalyticsDashboardService` | 9.2 | Designer / shares / templates |
| `AnalyticsReportService` + export helpers | 9.3 | Report builder |
| `BusinessAnalyticsService` | 9.5 | Portfolio BI |
| `SecurityAnalyticsService` | 9.6 | Security BI |
| `ExecutiveAnalyticsService` | 9.7 | Leadership composition |

## Adding a new executive widget

1. Aggregate in `ExecutiveAnalyticsService::buildWidgets()`.
2. Include key in `filterWidgetsForDashboard()` maps.
3. Render in `ExecutiveDashboardPage.vue`.
4. Extend `ExecutiveAnalyticsTest` JSON structure assertion.
5. Update `KPI-Definitions.md` if formulas change.

## Permissions

```
analytics.view | create | update | delete | export | manage
```

Policy: `AnalyticsPolicy` on `AnalyticsSubject`.

## Testing

```bash
php artisan test tests/Feature/Analytics
php artisan test tests/Feature/Monitoring
```

Prefer feature API tests with Sanctum + `RolesAndPermissionsSeeder`. Add unit tests for scoring formulas when changing MRR / business score / risk score.

## Known constraints (do not ignore)

1. Security analytics company filter is incomplete for logins/API keys (see Security Report).
2. `ensureHistory()` on reads can be expensive — prefer queued capture for production.
3. MRR is `SUM(subscriptions.amount)` without billing-period normalization.
4. Empty `Domains/Dashboard` scaffold is unused — Analytics owns dashboards.
