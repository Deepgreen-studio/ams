# Enterprise Analytics Domain

Phases **9.1**–**9.7** delivered; **9.8** review & documentation complete.

See the full index: [README.md](./README.md).

## Capabilities

- Categories: Business / Operational / Application / Customer / API / System / Security / Executive
- Configurable dashboards (personal, company, role, shared, template, system)
- Enterprise report builder (CSV / Excel / PDF / schedule)
- Business portfolio analytics + forecasting
- Security analytics (logins, ACL, GDPR, API keys, risk, heatmap)
- Executive role dashboards (CEO, Admin, Operations, Compliance, Support, Customer)
- Scorecards, performance indicators, monthly/quarterly/yearly trends
- Operational analytics + Monitoring health inputs

## Database (summary)

| Table | Purpose |
|-------|---------|
| `analytics_events` | Event stream |
| `analytics_dashboards` / `analytics_widgets` / shares | Dashboard builder |
| `analytics_reports` / `analytics_report_runs` | Report builder |
| `business_analytics_snapshots` | Business daily KPIs |
| `security_analytics_snapshots` | Security daily KPIs |
| `executive_analytics_snapshots` | Executive daily KPIs |

## Permissions

`analytics.view|create|update|delete|export|manage`

## Testing

```bash
php artisan test tests/Feature/Analytics tests/Feature/Monitoring
```

## Production readiness (9.8)

**Ready with follow-ups** for trusted internal analytics.  
**Not ready** for hard multi-tenant SaaS isolation or GAAP MRR claims.

Details: [reviews/Production-Readiness-Report.md](./reviews/Production-Readiness-Report.md).
