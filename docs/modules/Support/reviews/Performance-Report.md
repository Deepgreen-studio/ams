# Performance Report — Support Module

**Date:** 2026-08-04  
**Verdict:** Appropriate for enterprise mid-scale; schedule SLA evaluation should be queued under load.

## Score: 7.5 / 10

## Strengths

- Ticket lists paginated (default 15, max 100) with default eager-loads  
- Message conversation eager-loads author, attachments, reads  
- SLA evaluate batches open tickets with policy relations (limit 200)  
- Notifications dispatched on `notifications` queue (`ShouldQueue`)  
- Indexed foreign keys / status / due-date columns on tickets  

## Findings

| ID | Severity | Finding | Recommendation |
|----|----------|---------|----------------|
| P-01 | Medium | SLA evaluation runs synchronously every 5 minutes via Artisan command | Dispatch `EvaluateSupportSlaJob` |
| P-02 | Low | Dashboard uses multiple COUNT queries | Acceptable now; cache later |
| P-03 | Low | Search uses `LIKE %term%` | Full-text / Scout when volume grows |
| P-04 | Low | Conversation `attachment_count` separate query | withCount |
| P-05 | Info | Notification dispatch loops recipients | Fine; batch if >50 recipients typical |
| P-06 | Info | No dedicated analytics tables — dashboards recompute aggregates | Snapshot tables when Phase Analytics starts |

## Queues

| Queue | Usage |
|-------|--------|
| `notifications` | Templated email + database notifications |
| default / other | SLA job currently unused |

## Operational guidance

1. Run `queue:work --queue=notifications,default` in production.  
2. Monitor `support:evaluate-sla` duration; move to queue if >30s.  
3. Add Redis cache for Support dashboard stats if p95 API latency rises.
