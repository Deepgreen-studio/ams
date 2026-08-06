# Database Report — Analytics Module

**Milestone:** Phase 9.8  
**Score:** **7.4 / 10**

## Core tables

| Table | Purpose | Soft delete |
|-------|---------|-------------|
| `analytics_events` | Event stream | No |
| `analytics_dashboards` | Dashboards / saved views / templates | Yes |
| `analytics_widgets` | Widget layout | Yes |
| `analytics_dashboard_shares` | Share ACL | — |
| `analytics_reports` | Report definitions + designer | Yes |
| `analytics_report_runs` | Export artifacts | No |
| `business_analytics_snapshots` | Daily business KPIs | No |
| `security_analytics_snapshots` | Daily security KPIs | No |
| `executive_analytics_snapshots` | Daily executive KPIs + scorecards | No |

## Indexes / keys

- UUID unique on primary analytics entities and snapshots.  
- Unique `(company_id, snapshot_date)` on business/security/executive snapshots (short index names for MySQL).  
- Unique `(company_id, slug)` patterns on dashboards/reports elevations.

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| D-01 | High | Nullable `company_id` in unique pairs — MySQL allows multiple NULL company rows for same date under concurrency |
| D-02 | Medium | No soft deletes / retention job for snapshot fact tables |
| D-03 | Medium | Snapshot factories missing (DB standards checklist) |
| D-04 | Low | Report run binary/file retention not automated |
| D-05 | Info | Application-level `upsertForDate` is correct but not race-proof without locking |

## Recommendations

1. Add advisory lock or partial unique strategy for global (`company_id IS NULL`) snapshots.  
2. Add factories for the three snapshot models.  
3. Define retention (e.g. keep 400 days of daily snapshots; archive runs after 90 days).
