# Administrator Guide — Analytics

## Access control

Assign Spatie permissions carefully:

| Permission | Who |
|------------|-----|
| `analytics.view` | Analysts, managers, executives |
| `analytics.create` / `update` | Dashboard / report designers |
| `analytics.export` | Users who may download CSV/Excel/PDF / security exports |
| `analytics.delete` | Admins only |
| `analytics.manage` | Snapshot capture / advanced ops |

**Recommendation:** Do not grant `analytics.export` to all viewers once SoD is enforced (policy currently also allows VIEW for export — tracked as follow-up).

## Daily operations

1. Confirm queue workers run for report generation and scheduled reports.
2. Capture portfolio snapshots (Business / Security / Executive) via UI button or scheduled HTTP/job once per day.
3. Monitor disk usage under report run storage paths.
4. Review Monitoring health before trusting Executive System Health widgets.

## Multi-company notes

- Business/Executive customer & subscription metrics respect company filters.
- Security login / API key / heatmap metrics are **platform-global** today — do not present filtered security dashboards as tenant-isolated.
- For SaaS isolation, wait for Security Report remediations before enabling tenant self-serve analytics.

## Frontend navigation

Sidebar → **Analytics**. Subnav: Overview, Dashboards, Templates, Reports, Business, Executive, Security, Operational.

## Incident checklist

| Symptom | Check |
|---------|-------|
| Empty executive trends | Capture snapshots; trends need history |
| Slow executive page | Health probes + `ensureHistory` — see Performance Report |
| 403 on export | User needs `analytics.export` (middleware) |
| Wrong MRR vs finance | See KPI Definitions — amount sum ≠ GAAP MRR |

## Related modules

- Monitoring (`monitoring.view`) for live health
- Support SLA for SLA widgets
- Compliance analytics for compliance widgets
