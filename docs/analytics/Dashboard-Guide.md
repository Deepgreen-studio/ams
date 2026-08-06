# Dashboard Guide — Analytics

## Two dashboard systems

### 1. Configurable dashboards (Phases 9.1–9.2)

Build personal, company, role-shared, template, and system dashboards with widgets.

| Action | UI route | API |
|--------|----------|-----|
| List dashboards | `/analytics/dashboards` | `GET /api/v1/analytics/dashboards` |
| Designer | `/analytics/dashboards/:uuid/designer` | layout + widgets APIs |
| View | `/analytics/dashboards/:uuid` | `GET .../data` |
| Templates | `/analytics/templates` | `GET .../dashboards/templates` |

**Widget types:** KPI, charts, tables, maps, activity feed, notifications (see widget library).

**Sharing:** user / role / company shares via dashboard shares panel.

### 2. Executive role dashboards (Phase 9.7)

Pre-composed leadership boards (not designer layouts):

| Board | Route |
|-------|-------|
| CEO | `/analytics/executive` |
| Admin | `/analytics/executive/admin` |
| Operations | `/analytics/executive/operations` |
| Compliance | `/analytics/executive/compliance` |
| Support | `/analytics/executive/support` |
| Customer | `/analytics/executive/customer` |
| Scorecards | `/analytics/executive/scorecards` |
| Trends | `/analytics/executive/trends` |
| Forecast | `/analytics/executive/forecast` |

**Widgets on boards:** Top Customers, Top Applications, Revenue, Support SLA, Compliance Status, System Health, Growth Metrics.

## Related portfolio boards

| Board | Route |
|-------|-------|
| Business | `/analytics/business` |
| Security | `/analytics/security` |
| Operational | `/analytics/operational` |

## Permissions

Requires `analytics.view`. Capturing executive/business/security snapshots requires `analytics.manage`.

## Tips

1. Prefer **Capture snapshot** once per day (or schedule a job) rather than relying on read-path persistence under load.
2. Use date filters to compare periods; trends need historical snapshots for monthly/quarterly/yearly buckets.
3. System dashboards cannot be deleted (by design).
