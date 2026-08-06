# Analytics Validation Report — Analytics Module

**Milestone:** Phase 9.8  
**Score:** **6.8 / 10** (operationally useful; not finance-grade)

## Surfaces validated

| Surface | Status | Notes |
|---------|--------|-------|
| Foundation events / categories | ✅ | 8 categories including Executive |
| Dashboard builder | ✅ | Templates, shares, layout |
| Report builder | ✅ | CSV/Excel/PDF path covered |
| Business BI | ✅ | KPIs + charts + forecast |
| Security analytics | ✅ | KPIs + timeline + export |
| Executive boards | ✅ | Role boards + scorecards + trends |
| Forecasting | ✅ | Linear business forecast |
| Monitoring inputs | ✅ | Used by Executive system health |

## Validation findings

| ID | Severity | Finding |
|----|----------|---------|
| V-01 | High | MRR ≠ true MRR (no billing-period normalization) |
| V-02 | High | Company filter inconsistent across Security vs Business/Executive |
| V-03 | Medium | Business / risk / SLA scores use undocumented hardcoded thresholds |
| V-04 | Medium | Executive trends fall back to business series when snapshots empty |
| V-05 | Low | Foundation event count flake fixed — factory `occurred_at` must sit inside overview window |
| V-06 | Info | Testing stubs Monitoring health to avoid probe latency in CI |

## KPI trust matrix

| Audience | Trust level |
|----------|-------------|
| Product / ops daily steering | High (directional) |
| Customer success | Medium–High |
| Finance / board MRR | Low until V-01 fixed |
| Tenant self-serve security | Low until S-01 fixed |

See also: [KPI Definitions](../KPI-Definitions.md).
