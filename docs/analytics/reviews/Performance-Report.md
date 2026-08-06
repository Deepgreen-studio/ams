# Performance Report — Analytics Module

**Milestone:** Phase 9.8  
**Score:** **5.8 / 10** under production load for Executive boards  
**Score:** **7.5 / 10** for warm snapshot / light list APIs

## Hot paths

| Path | Cost driver |
|------|-------------|
| Business overview / growth / forecast | `ensureHistory()` re-aggregates + persists today; synthetic fill per missing day |
| Security overview / audit | Same `ensureHistory()` pattern |
| Executive any board | Business overview + growth + forecast + Security overview + SLA + Compliance + **live Monitoring inspect** + persist snapshot |
| Report run | Spreadsheet/PDF generation — should stay queued |

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| P-01 | **Critical** | Read paths write snapshots (`ensureHistory` / `persistTodaySnapshot`) |
| P-02 | **Critical** | Cold 30-day ranges can trigger hundreds of queries via per-day synthetic aggregation |
| P-03 | High | Executive boards run live health probes on every page load (stubbed only in `testing`) |
| P-04 | High | No Redis/`Cache::remember` for KPI aggregates |
| P-05 | Medium | Request-level caches in Executive help within one request only |
| P-06 | Info | Eager loads on top customers/apps mitigate classic N+1 |

## Test evidence (Phase 9.8 run)

- Analytics feature suite: **28** tests  
- Monitoring feature suite: **7** tests  
- Executive multi-board test historically ~25–30s before monitoring stub; still heavier than other suites due to multi-service composition  

## Recommendations

1. Move snapshot build to scheduled jobs (`analytics.capture.*`); reads should only query snapshots + light live deltas.  
2. Cache Monitoring dashboard scores for 30–60s.  
3. Cap cold-history backfill (e.g. max 7 synthetic days per request).  
4. Keep report generation on queue with memory limits.
