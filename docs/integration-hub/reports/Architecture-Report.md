# Architecture Report — Integration Hub (Phase 2.8)

**Verdict:** Architecture is modular-monolith compliant and production-shaped. Shared engines correctly own cross-cutting I/O; domains own policy and orchestration.

## Layering

```
Vue Modules (integrations / webhooks / sync / mappings / queue / monitoring)
        │  REST JSON
        ▼
Domain Controllers (thin) + Form Requests + Policies + Resources
        │
        ▼
Domain Services (Integrations / Queue / Monitoring)
        │
        ├── Shared Engines (Http, Webhook, Sync, Mapping, Queue, Monitoring)
        │
        ▼
Repositories → Eloquent Models → MySQL
```

## Compliance checklist

| Principle | Status | Evidence |
|-----------|--------|----------|
| DDD domain isolation | Pass | Integrations, Queue, Monitoring domains; engines under Shared |
| Controllers thin | Pass | Controllers inject services only |
| Repository pattern | Pass | Domain repositories for persistence |
| Service layer | Pass | Connection, Webhook, Sync, Mapping, QueueMonitor, Monitoring |
| REST / JSON only | Pass | `ApiResponse` patterns; no Blade |
| SOLID / DI | Pass | Constructor injection throughout engines |
| Queue-first long work | Pass | Webhook delivery, sync runs, sample notifications |
| Event-ready | Partial | Webhook event catalog + dispatch; broader domain events optional |

## Shared engines

| Engine | Path | Responsibility |
|--------|------|----------------|
| HTTP | `Shared/Services/Http` | Auth, retry, timeout, rate limit, parse |
| Webhook | `Shared/Services/Webhook` | Sign, dispatch, receive, retry |
| Sync | `Shared/Services/Sync` | Import/export, conflict, schedule helper |
| Mapping | `Shared/Services/Mapping` | Field map, rules, validate, transform |
| Queue | `Shared/Services/Queue` | Manager, monitor, track middleware |
| Monitoring | `Shared/Services/Monitoring` | Metrics, score, alerts |

Outbound HTTP from domain code is constrained: only `ApiClientService` / `RequestBuilder` touch Laravel `Http::`.

## Frontend architecture

Composition API modules with stores/services/pages; Admin layout routes gated by permission meta. Subnav components keep hub UX consistent.

## Gaps / tech debt

1. **Unit vs feature balance** — engines are validated mainly via Feature tests; few pure Unit tests under `tests/Unit`.
2. **Dual QueueManager names** — `Shared/Services/QueueManager.php` and `Shared/Services/Queue/QueueManager.php` coexist; consolidate naming in a later cleanup (non-blocking if unused paths are clear).
3. **SSRF guardrail** — architecture does not yet include URL allowlist / private-IP blocking at the HTTP engine boundary (see Security Report).
4. **Multi-tenant SaaS** — company scoping exists; full tenant isolation plugins remain future-ready, not fully productized.

## Score

**Architecture readiness: 88 / 100**
