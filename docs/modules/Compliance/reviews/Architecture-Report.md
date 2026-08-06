# Architecture Report — Compliance Module

**Date:** 2026-08-04  
**Scope:** Phases 7.1–7.8  
**Verdict:** Strong modular DDD design aligned with AMS standards; ready for trusted multi-company admin use with follow-ups before hard multi-tenant SaaS and full GDPR automation.

## Score: 8.0 / 10

## Pattern compliance

| Principle | Assessment |
|-----------|------------|
| DDD domain isolation | ✅ `Domains/Compliance` |
| Thin controllers | ✅ Authorize + service + `ApiResponse` |
| Service + repository | ✅ Consistently applied |
| Events / listeners | ✅ Activity + notifications per submodule |
| API-first JSON | ✅ |
| Enums / Form Requests / Resources | ✅ |
| Interfaces / Contracts | ⚠️ Concrete DI only |
| Naming (PolicyDocument) | ✅ Avoids Laravel Policy clash |

## Layer map

```
Controller → FormRequest → Policy/Middleware → Service → Repository → Model
                                      ↘ Events → Listeners (activity, notifications)
```

### Subsystems

| Subsystem | Service | Notes |
|-----------|---------|-------|
| Cases | `ComplianceCaseService` | CRUD, assign, dashboard |
| Privacy | `PrivacyRequestService` | DSAR workflow, export, deletion confirm |
| Consent | `ConsentService` | Types, grant/withdraw, preferences |
| Breaches | `DataBreachService` | Lifecycle + 72h deadline |
| DPIA / Risk | `DpiaService` | Wizard + risk register (combined controller) |
| Policies | `PolicyDocumentService` | Versions, approvals, CMS link |
| Analytics | `ComplianceAnalyticsService` | Live aggregates + export |

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| A-01 | Medium | Large services (breach/privacy/DPIA/policy 500–800 LOC) — consider extracting workflow collaborators |
| A-02 | Medium | `DpiaController` owns DPIA + Risk aggregates |
| A-03 | Medium | No repository/service interfaces under `Contracts/` |
| A-04 | Low | Form Request `authorize()` always true; Gate used in controllers |
| A-05 | Info | Domain `Tests/` unused — tests live under `tests/Feature/Compliance` |
| A-06 | Info | Cross-domain analytics reads Audit `ActivityLog` (acceptable) |

## Recommendations

1. Split Risk Register into dedicated service/controller when Phase 8 expands risk.  
2. Introduce interfaces for core repositories before microservice extraction.  
3. Keep PolicyDocument naming; document `policies` table in ops runbooks.  

## Inventory

| Asset | Count |
|------:|------:|
| Controllers | 7 |
| Services | 7 |
| Models | 15 |
| Laravel Policies | 6 |
| Feature test files | 7 |
| Frontend pages | ~43 |
| API routes (`compliance`) | ~99 |
