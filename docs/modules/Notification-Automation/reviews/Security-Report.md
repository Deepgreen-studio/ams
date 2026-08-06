# Security Report — Phase 8 Notification & Automation

**Milestone:** Phase 8.8  
**Date:** 2026-08-05

## Summary

Authentication and route-level authorization are consistently applied (`auth:sanctum`, `throttle:api`, Spatie `permission:*`). AI credentials handling is strong. Highest risks are privileged automation actions, plaintext channel configs, and incomplete engagement tracking (not a direct exploit, but can mislead audits).

**Staff security score: 7.0 / 10**  
**Multi-tenant SaaS isolation score: 5.0 / 10** (company filters exist; hard tenant isolation not proven across all queries)

## Findings

| ID | Severity | Finding | Recommendation |
|----|----------|---------|----------------|
| S-01 | High | Automation `assign_role` can assign any Spatie role | Require `roles.assign` (or approval workflow) before executing |
| S-02 | High | Automation `generate_api_key` mints Sanctum tokens | Require `integrations.manage` / explicit allow-list; never silent mint |
| S-03 | Medium | System actor fallback to first user in some automation paths | Use dedicated system user / null-safe service account |
| S-04 | Medium | Notification channel `config` JSON not encrypted | Encrypt secrets at rest (same pattern as AI credentials) |
| S-05 | Medium | AI daily token limit surfaced but enforcement incomplete | Enforce in `AiAssistantService` before provider calls |
| S-06 | Low | Personal notification endpoints skip Gate authorize | Keep ownership checks (present); add policy methods for clarity |
| S-07 | Low | Analytics company scoping depends on filter params | Add tenant isolation feature tests (IDOR/company mismatch) |
| S-08 | Info | Click tracking not wireable by attackers yet | Wire intentionally with auth + ownership |

## Controls that are healthy

- Sanctum SPA auth + API throttle
- Spatie permission middleware on admin routes
- Policies registered for major models + AnalyticsSubject
- AI credentials: `encrypted:array`, `$hidden`, activity log exclusion
- Scheduler custom commands allow-listed
- Soft deletes on key definition tables
- Export endpoints permission-gated (`analytics.export`)

## Permission matrix (Phase 8 modules)

| Module | Permissions |
|--------|-------------|
| notifications | view, create, update, delete, approve, publish |
| automation | view, create, update, delete, manage |
| workflows | view, create, update, delete, manage, approve |
| scheduler | view, create, update, delete, manage, retry |
| ai | view, create, update, delete, manage, chat |
| analytics | view, export |

## Go / No-Go (security lens)

| Scenario | Decision |
|----------|----------|
| Internal trusted-admin deployment | **GO** with privileged-action training |
| Untrusted multi-tenant SaaS | **NO-GO** until tenant proofs + action hard gates |
| External AI with real keys | **GO** if credentials encrypted + least privilege roles |
