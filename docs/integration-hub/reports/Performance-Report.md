# Performance Report — Integration Hub (Phase 2.8)

**Verdict:** Design is queue-oriented and scalable with Redis. Database-backed queues are acceptable for smaller deployments; Redis + horizontal workers recommended for production SLAs.

## Design strengths

| Area | Implementation | Benefit |
|------|----------------|---------|
| Outbound retries / timeout | `RetryManager`, `TimeoutManager` | Avoids hanging PHP-FPM workers |
| Outbound rate limit | `RateLimitManager` (cache keyed) | Protects partners and AMS |
| Webhook delivery async | `DeliverOutgoingWebhookJob` on `webhooks` | Fast API responses |
| Sync async | `RunIntegrationSyncJob` on `syncs` | Large imports off HTTP |
| Priority queues | `config/ams_queue.php` | Critical work first |
| Scheduler withoutOverlapping | `console.php` | Prevents sync/monitor stampedes |
| Monitoring snapshots | Every 5 minutes | Bounded aggregation cost |
| Connection connect timeout | `min(10, timeout)` | Faster failure on dead hosts |

## Bottlenecks & recommendations

| Risk | Severity | Mitigation |
|------|----------|------------|
| `QUEUE_CONNECTION=database` under load | Medium | Prefer Redis for queue + cache in production |
| Non-atomic rate limit counter | Medium | Atomic increment (see Security SEC-04) |
| Large sync payload in DB logs | Medium | Truncation / sampling for `sync_logs` bodies |
| N+1 on list endpoints | Low | Repositories already eager-load where tested; continue reviewing new filters |
| Monitoring capture sync aggregation | Low | Keep on schedule; avoid overlapping manual capture floods |
| Single worker process | High (ops) | Run multiple supervised workers per queue set |

## Capacity guidance (initial)

| Workload | Suggested baseline |
|----------|--------------------|
| Webhooks | 2+ workers listening to `webhooks,high` |
| Sync | 1–2 workers on `syncs,imports,exports` |
| Notifications | 1 worker on `notifications,low,default` |
| Scheduler | Single `schedule:work` or cron `* * * * *` |
| Cache / queue | Redis 6+ |

## Score

**Performance readiness: 80 / 100** (with Redis + multi-worker ops)
