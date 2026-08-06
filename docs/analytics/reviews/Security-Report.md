# Security Report — Analytics Module

**Milestone:** Phase 9.8  
**Score (staff / single-tenant):** **6.5 / 10**  
**Score (multi-tenant SaaS):** **3.5 / 10** — **Block** until company scoping + SoD fixes

## Controls in place

- Sanctum `auth:sanctum` on all analytics routes  
- Spatie `permission:analytics.*` middleware  
- Controller `authorize()` against `AnalyticsSubject` / `AnalyticsPolicy`  
- Capture endpoints require `analytics.manage`  
- Report run/download middleware uses `analytics.export`

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| S-01 | **Critical** | Security analytics company filter incomplete — logins, heatmap, API logs, CMS keys, Sanctum tokens remain global when `company` is set |
| S-02 | High | `AnalyticsPolicy::export` allows `analytics.view` OR `analytics.export` — weak SoD |
| S-03 | High | Security export controller authorizes `viewAny` while route middleware requires EXPORT (policy/controller drift) |
| S-04 | High | Failed-login IP/browser and customer emails exposed to analytics viewers/exporters |
| S-05 | High | Frontend routes lack permission meta — any authenticated user can open export UIs |
| S-06 | Medium | Capture endpoints use raw `Request` without FormRequest validation |
| S-07 | Medium | No IDOR / company-isolation feature tests for analytics filters |
| S-08 | Info | Failed login recording with nullable `user_id` is correct for unknown emails |

## Must-fix before SaaS tenant self-serve

1. Scope security sources by company (or explicitly document global-only metrics and hide under company filter).  
2. Tighten `AnalyticsPolicy::export` to EXPORT only; align authorize() calls.  
3. Add company isolation tests.  
4. Gate frontend routes with permission checks.  
5. Minimize PII in default timeline/export payloads (or require elevated permission).

## Acceptable for

Internal AMS operators with trusted roles on a single deployment / company portfolio.
