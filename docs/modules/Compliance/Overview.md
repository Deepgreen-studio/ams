# Compliance Documentation (Overview)

**Module:** `Domains/Compliance`  
**Stack:** Laravel 12 API + Vue 3 admin UI  
**Phases:** 7.1–7.8  

## Purpose

Centralize enterprise compliance operations for AMS: case management, GDPR subject rights, consent records, personal-data breach response, DPIA & risk, policy governance, and cross-module analytics.

## Architecture

```
Controller → FormRequest → Gate Policy + permission middleware
         → Service → Repository → Model / MySQL
         ↘ Events → Activity listeners + Notification listeners
```

| Layer | Location |
|-------|----------|
| Domain | `backend/app/Domains/Compliance/` |
| Routes | `Routes/api.php` under `/api/v1/compliance` |
| Frontend | `frontend/src/modules/compliance/` |
| Tests | `backend/tests/Feature/Compliance/` |
| Docs | `docs/modules/Compliance/` |

## Submodules

| Area | Backend service | Primary tables |
|------|-----------------|----------------|
| Cases | `ComplianceCaseService` | `compliance_cases` |
| Privacy / DSAR | `PrivacyRequestService` | `privacy_requests`, `privacy_request_logs` |
| Consent | `ConsentService` | `consent_types`, `user_consents`, `consent_history` |
| Breaches | `DataBreachService` | `data_breaches`, `breach_actions`, `breach_notifications` |
| DPIA & Risk | `DpiaService` | `dpia_assessments`, `risk_register`, `risk_actions` |
| Policies | `PolicyDocumentService` | `policies`, `policy_versions`, `policy_approvals` |
| Analytics | `ComplianceAnalyticsService` | Aggregates across compliance + `activity_log` |

## Model naming note

Laravel Gate “policies” conflict with business policy documents. Business model is **`PolicyDocument`** mapped to table **`policies`**. Authorization class is **`PolicyDocumentPolicy`**.

## Events & audit

Lifecycle events are logged with Spatie Activity Log under `log_name = compliance`, plus domain-specific timelines (privacy logs, consent history).

## Frontend surfaces

- Subnav: Cases, Privacy, Consents, Breaches, DPIA, Policies, Analytics  
- Patterns: dashboards, list/detail, workflows, version timeline/compare, report exports  

## Related docs

- [GDPR.md](./GDPR.md)  
- [Privacy-Guide.md](./Privacy-Guide.md)  
- [Administrator-Guide.md](./Administrator-Guide.md)  
- [Developer-Guide.md](./Developer-Guide.md)  
- [API.md](./API.md) · [Database.md](./Database.md)  
