# Performance Report — Compliance Module

**Date:** 2026-08-04  
**Scope:** List endpoints, dashboards, analytics aggregates, exports

## Score: 7.2 / 10

## Strengths

- List repositories paginate with `per_page` caps (≤ 100).  
- Show endpoints eager-load common relations.  
- Analytics default window is 30 days.  
- Export for analytics is KPI-sized (not full table dumps).

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| P-01 | High | Privacy AVG resolution loads all completed rows into PHP (`ComplianceAnalyticsRepository::privacyMetrics`) — should use SQL aggregate on MySQL |
| P-02 | High | Overview dashboard issues many independent daily `GROUP BY DATE(...)` series — cost grows with range length; no cache |
| P-03 | Medium | Missing composite indexes `(company_id, created_at)` for analytics filters |
| P-04 | Medium | Audit analytics scans all `compliance` activity without company filter |
| P-05 | Low | Fragile `company_id` detection via `$fillable` in `dailyCounts` |
| P-06 | Low | Large services increase cognitive cost more than runtime cost |

## Recommendations

1. Replace PHP average with driver-aware SQL (`TIMESTAMPDIFF` on MySQL; keep PHP fallback for SQLite tests).  
2. Add composite indexes for high-cardinality analytics tables.  
3. Cache dashboard payloads (e.g. 60–300s per company+range key) behind Redis.  
4. Cap analytics `from/to` range (e.g. max 366 days) in Form Request.  
5. Consider nightly snapshot table if tenant volumes exceed interactive query budgets.

## Load guidance (qualitative)

| Scale | Expectation |
|-------|-------------|
| < 10k privacy requests / company | Current design OK |
| 100k+ completed DSARs | Fix P-01 before relying on analytics |
| Multi-year unbounded date range | Enforce P-05-style caps |
