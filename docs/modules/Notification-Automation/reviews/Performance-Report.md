# Performance Report — Phase 8 Notification & Automation

**Milestone:** Phase 8.8  
**Date:** 2026-08-05

## Summary

Core write paths use queues where it matters (notification dispatch, scheduled job execution). Indexes exist on status/time columns for logs and instances. Main performance risks are live analytics aggregation, synchronous fan-out before queue, and missing caching for dashboards.

**Performance score: 7.2 / 10**

## Strengths

1. Notification templated sends implement `ShouldQueue`.
2. Scheduler runs execute via queued `ExecuteScheduledJob`.
3. Minute processors use `withoutOverlapping` and job limits where applicable.
4. Delivery / automation / workflow / AI log tables are indexed for common filters.
5. Automation customer notify is capped (200) to avoid unbounded fan-out.

## Risks

| ID | Area | Risk | Mitigation |
|----|------|------|------------|
| P-01 | Analytics | Multi-count live dashboard queries | Cache KPIs (Redis) or daily snapshots |
| P-02 | Notifications | Sync create-per-channel before queue | Batch insert / chunk dispatch |
| P-03 | Automation | Loop creates for notify/tasks | Queue per-recipient jobs |
| P-04 | AI | Chat latency + token cost | Rate limits + daily token hard stop |
| P-05 | Workflows | Complex parallel graphs | Load-test gateway resolution |
| P-06 | Scheduler | Stub success hides real work cost | Implement real handlers carefully with timeouts |

## Index coverage (selected)

| Table | Useful indexes present |
|-------|------------------------|
| `notifications` | status, read/sent/scheduled, user, clicked_at |
| `notification_logs` | status/channel/event + FKs |
| `automation_rules` | trigger/event/enabled, next_run_at |
| `automation_logs` | status, created_at |
| `workflow_instances` | status, due_at, subject morph |
| `scheduled_jobs` | next_run, handler, enabled |
| `ai_usage_logs` | feature/driver/status + company created |

## Operational SLOs (recommended)

| Surface | Target |
|---------|--------|
| Notification center list | p95 < 300ms (cached counts optional) |
| Analytics dashboard (30d) | p95 < 1.5s initially; cache if exceeded |
| Automation process tick | complete within minute window |
| AI chat (null/local) | p95 < 200ms; remote providers separate budget |

## Recommendations

1. Add Redis cache for Analytics dashboard KPIs (TTL 60–300s).
2. Enforce AI token budgets before provider HTTP calls.
3. Monitor queue depth for `notifications` and `scheduler` queues.
4. Avoid N+1 in new report endpoints — keep eager loads / aggregates.
5. When click tracking is wired, write asynchronously (don’t block open).
