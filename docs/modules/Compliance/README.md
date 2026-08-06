# Compliance Module Documentation Index

Enterprise Compliance, GDPR, Privacy, Consent, Breach, DPIA, Policy Governance, and Analytics for AMS (Phases **7.1–7.8**).

## Guides

| Document | Audience |
|----------|----------|
| [Overview.md](./Overview.md) | Compliance documentation (module map) |
| [GDPR.md](./GDPR.md) | GDPR documentation |
| [Privacy-Guide.md](./Privacy-Guide.md) | Privacy / DSAR handling |
| [Administrator-Guide.md](./Administrator-Guide.md) | Platform administrators |
| [Developer-Guide.md](./Developer-Guide.md) | Backend / frontend engineers |
| [API.md](./API.md) | Integrators |
| [Database.md](./Database.md) | Schema & relationships |
| [../Compliance.md](../Compliance.md) | Short module pointer |

## Phase 7.8 review reports

| Report | Path |
|--------|------|
| Architecture | [reviews/Architecture-Report.md](./reviews/Architecture-Report.md) |
| Security | [reviews/Security-Report.md](./reviews/Security-Report.md) |
| Performance | [reviews/Performance-Report.md](./reviews/Performance-Report.md) |
| Compliance | [reviews/Compliance-Report.md](./reviews/Compliance-Report.md) |
| Testing | [reviews/Testing-Report.md](./reviews/Testing-Report.md) |
| Production Readiness | [reviews/Production-Readiness-Report.md](./reviews/Production-Readiness-Report.md) |

## Capability map

| Capability | Phase | Status |
|------------|-------|--------|
| Compliance cases | 7.1 | Complete |
| Privacy requests (DSAR) | 7.2 | Complete (erasure = confirmation workflow; real purge backlog) |
| Consent management | 7.3 | Complete |
| Data breaches | 7.4 | Complete (72h deadline tracking; outbound notify bookkeeping) |
| DPIA & risk register | 7.5 | Complete |
| Policy & document governance | 7.6 | Complete (immutable versions) |
| Compliance analytics | 7.7 | Complete (CSV/Excel; PDF architecture-ready) |
| Module review & docs | 7.8 | Complete |

## Permissions

| Permission | Use |
|------------|-----|
| `compliance.view` | Dashboards, lists, reports, analytics export |
| `compliance.create` | Create cases, requests, consents, breaches, DPIAs, policies |
| `compliance.update` | Updates, workflow transitions, approvals (coarse) |
| `compliance.delete` | Soft delete |
| `compliance.manage` | Elevated operations (types, restore, etc.) |

## Stop / approval

Phase 7.8 review complete. **Do not start Phase 8 without explicit approval.**
