# Analytics Module Documentation

**Domain:** Analytics (Phases 9.1–9.8)  
**Related:** Monitoring (health probes for Executive), Support SLA, Compliance analytics sources

## Guides

| Guide | Audience | Path |
|-------|----------|------|
| [Overview](./Overview.md) | Everyone | Capabilities, tables, API summary |
| [Dashboard Guide](./Dashboard-Guide.md) | Admins / Analysts | Dashboard builder + executive boards |
| [Report Builder Guide](./Report-Builder-Guide.md) | Analysts | Designer, schedule, export |
| [Developer Guide](./Developer-Guide.md) | Engineers | Domain map, APIs, extension points |
| [Administrator Guide](./Administrator-Guide.md) | Ops / Admins | Permissions, capture jobs, ops checklist |
| [KPI Definitions](./KPI-Definitions.md) | Product / Finance | Formulas, approximations, caveats |

## Phase 9.8 Reviews

| Report | Path |
|--------|------|
| Architecture | [reviews/Architecture-Report.md](./reviews/Architecture-Report.md) |
| Database | [reviews/Database-Report.md](./reviews/Database-Report.md) |
| Security | [reviews/Security-Report.md](./reviews/Security-Report.md) |
| Performance | [reviews/Performance-Report.md](./reviews/Performance-Report.md) |
| Analytics Validation | [reviews/Analytics-Report.md](./reviews/Analytics-Report.md) |
| Testing | [reviews/Testing-Report.md](./reviews/Testing-Report.md) |
| Production Readiness | [reviews/Production-Readiness-Report.md](./reviews/Production-Readiness-Report.md) |

## Quick test command

```bash
cd backend
php artisan test tests/Feature/Analytics tests/Feature/Monitoring
```

## Status after Phase 9.8

**Ready with follow-ups** for single-tenant / trusted super-admin analytics.  
**Not ready** for hard multi-tenant SaaS isolation or board claims of true GAAP MRR without KPI remediation.
