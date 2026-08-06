# Testing Report — Analytics Module

**Milestone:** Phase 9.8  
**Score:** **7.4 / 10**

## Suites executed

```bash
php artisan test tests/Feature/Analytics tests/Feature/Monitoring
```

| Suite | Files | Tests | Result |
|-------|------:|------:|--------|
| Analytics Feature | 7 | 28 | Pass (after foundation date fix) |
| Monitoring Feature | 2 | 7 | Pass |
| Analytics Unit | 0 | 0 | — |

## Coverage by area

| Area | Feature coverage | Gaps |
|------|------------------|------|
| Foundation | Yes | — |
| Dashboard builder | Yes | Permission matrix edges |
| Report builder | Yes | Large-file / queue failure paths |
| Business | Yes | Company isolation |
| Security | Yes | Company isolation, export SoD |
| Executive | Yes | Without Monitoring stub |
| Platform ops analytics | Yes | — |
| Monitoring health / enterprise | Yes | — |
| Unit (MRR, scores, forecast) | No | Add |

## Fixes applied in Phase 9.8

- `EnterpriseAnalyticsFoundationTest` now sets `occurred_at` inside the default overview window to avoid flaky event counts from factory `dateTimeBetween('-30 days', 'now')`.

## Recommended next tests

1. Company A vs B isolation for business + security filters.  
2. Export forbidden for `analytics.view` only (after policy fix).  
3. Unit tests for `calculateBusinessScore`, security risk score, forecast slope.  
4. Capture FormRequest validation failures.  
5. Concurrent snapshot upsert under nullable company_id.
