# Architecture Report — Support Module

**Date:** 2026-08-04  
**Scope:** Phases 6.1–6.8  
**Verdict:** Production-shaped modular design; ready for single-tenant / trusted multi-company admin use with follow-ups before SaaS hard isolation.

## Score: 8.2 / 10

## Pattern compliance

| Principle | Assessment |
|-----------|------------|
| DDD domain isolation | ✅ `Domains/Support` + `Domains/Notifications` |
| Thin controllers | ✅ Tickets / conversation / portal / SLA |
| Service + repository | ✅ Core flows |
| Events | ✅ 13+ Support events; activity + notifications |
| API-first JSON | ✅ `ApiResponse` envelope |
| SOLID / typed PHP | ✅ Enums, Form Requests, Resources |

## Layer map

```
Controller → FormRequest → Policy/Middleware → Service → Repository → Model
                                      ↘ Events → Listeners (activity, notifications)
```

### Subsystems

| Subsystem | Service(s) | Notes |
|-----------|------------|-------|
| Tickets | `SupportTicketService` | CRUD, source portal default |
| Workflow | `SupportTicketWorkflowService`, `AssignmentService` | Transitions, kanban, queue |
| Conversation | `SupportTicketConversationService` | Visibility, attachments |
| Portal | `PortalSupportTicketService` | Customer scoping |
| SLA | `SupportSlaService`, `Tracking`, `BusinessHours` | Policies, evaluate command |
| Knowledge | `KnowledgeBaseService` | Versions, CMS link, feedback |
| Canned | `SupportCannedResponseService` | Personal/shared |
| Notifications | `NotificationDispatchService` + Support listener | Templated email + DB |

## Findings

| ID | Severity | Finding |
|----|----------|---------|
| A-01 | Medium | Domain `Tests/` folder empty — feature tests live under `tests/Feature` |
| A-02 | High | Only `SupportTicketPolicy`; KB/SLA/canned rely on route middleware |
| A-03 | High | Sparse factories (mainly `SupportTicketFactory`) |
| A-04 | Medium | `EvaluateSupportSlaJob` unused; console command runs inline on schedule |
| A-05 | Low | Legacy `Support/Notifications/*` classes superseded by templated dispatcher |
| A-06 | Info | Analytics domain empty — Support uses dashboard aggregates only |
| A-07 | Low | No repository interfaces/`Contracts` folder |

## Database architecture

17+ tables across tickets, conversation, SLA, KB, canned, notification system. UUIDs, FKs, soft deletes on primary entities. Gaps: KB/canned lack `company_id` for hard tenant isolation.

## Recommendations

1. Add policies + factories for KB, SLA, canned, messages before Phase 7 expansion.  
2. Queue SLA evaluation via `EvaluateSupportSlaJob`.  
3. Remove or alias dead legacy notification classes.  
4. Plan Support Analytics domain when Reports module starts.
