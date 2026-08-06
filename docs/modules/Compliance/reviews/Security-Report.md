# Security Report — Compliance Module

**Date:** 2026-08-04  
**Scope:** Phases 7.1–7.8 (`Domains/Compliance`, `frontend/src/modules/compliance`)  
**Method:** Manual architecture/security audit of Compliance domain. Automated branch-diff Security Review subagent could not compute a git diff in this workspace state.

## Score: 6.8 / 10 (staff API) · 4.0 / 10 (hard multi-tenant)

## Controls present

| Control | Status |
|---------|--------|
| Sanctum auth on all routes | ✅ |
| Spatie permission middleware | ✅ `compliance.view\|create\|update\|delete\|manage` |
| Laravel Gate policies | ✅ 6 policy classes |
| UUID route identifiers | ✅ |
| Activity logging | ✅ `log_name=compliance` |
| Soft deletes on primaries | ✅ |
| Form request validation | ✅ |
| Policy version immutability (app-level) | ✅ |

## Findings

| ID | Severity | Location | Finding |
|----|----------|----------|---------|
| S-01 | Critical | `PrivacyRequestService::confirmDeletion` | Deletion confirmation does not erase/anonymize related personal data — risk of false RtbF assurance |
| S-02 | High | `*Policy.php` (all) | No company/tenant membership checks — UUID IDOR across companies for any user with `compliance.view` |
| S-03 | High | `CompliancePermission` | Coarse permissions; approve/publish/notify share `update` — weak separation of duties |
| S-04 | High | `PrivacyRequestResource` | `export_payload` / file path may expose sensitive subject data in API responses |
| S-05 | Medium | Frontend router / Subnav | No `can()` UI gating — relies on API 403 only |
| S-06 | Medium | `DataBreachService` notify | Notification “send” is bookkeeping; no verified outbound channel |
| S-07 | Medium | Analytics audit queries | Activity log metrics ignore `company_id` scope |
| S-08 | Low | Cascade FKs on log tables | Hard delete of parent can destroy audit children |
| S-09 | Low | Policy version rows | Still Eloquent-updatable; immutability not DB-enforced |

## Threat model notes

- **Trusted internal admin (single org):** Acceptable with trained operators and limited `compliance.*` assignment.  
- **Multi-tenant SaaS:** **Not ready** until S-02 (tenant isolation) is fixed.  
- **Regulatory claim of automated erasure:** **Not ready** until S-01 pipeline exists.

## Recommendations (priority)

1. Enforce `company_id` (or membership) in every Gate `view/update/delete`.  
2. Implement erasure/anonymization jobs behind deletion confirmation — or label UI as “manual confirm only”.  
3. Split permissions (`privacy.approve`, `policies.publish`, `breach.notify`, …).  
4. Strip `export_payload` from list resources; authorize download endpoint only.  
5. Scope analytics activity queries by subject company when filter present.  
6. Add frontend permission helpers before Phase 8 UX polish.
