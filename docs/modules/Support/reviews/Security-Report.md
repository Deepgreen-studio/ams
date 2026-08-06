# Security Report — Support Module

**Date:** 2026-08-04  
**Verdict:** Solid for internal staff ops and portal ownership checks; **not** hard multi-tenant SaaS ready without company scoping.

## Score: Admin authZ 7.5 / 10 · Multi-tenant isolation 4.0 / 10

## Controls that work

| Control | Status |
|---------|--------|
| Sanctum auth on all Support/Portal/Notifications APIs | ✅ |
| Spatie permissions on admin routes | ✅ |
| Portal requires `customer` role + `customer_id` | ✅ |
| Portal ticket ownership by `customer_id` | ✅ |
| Portal conversation filtered to `public` | ✅ |
| Attachments on private disk; access via auth endpoints | ✅ |
| Canned shared creation gated by `support.manage` | ✅ |
| Customer replies forced to public + author_type customer | ✅ |

## Findings

| ID | Severity | Finding | Remediation |
|----|----------|---------|-------------|
| S-01 | **Critical** | `SupportTicketPolicy::view` allows any user with `support.view` to open any ticket — no company binding | Scope policy + default queries by allowed companies |
| S-02 | **High** | Notification managers resolved as **all** `support.manage` users globally | Filter managers by ticket `company_id` / membership |
| S-03 | **High** | Portal has no attachment download/preview API or UI | Add portal attachment routes with ownership checks |
| S-04 | **Medium** | Message HTML uses permissive `strip_tags`; no URL scheme hardening | HTMLPurifier / allow-list + `noopener` |
| S-05 | **Medium** | Ticket description / KB / canned bodies not server-sanitized | Central purifier in services |
| S-06 | **Medium** | Portal reply request lacks `max` length (admin has 100000) | Align validation |
| S-07 | **Low** | SVG allowed in attachments — XSS risk if previewed inline | Restrict SVG or sanitize on serve |
| S-08 | **Medium** | KB & canned are globally shared (no `company_id`) | Accept for single-tenant; partition for SaaS |

## Portal threat model

| Threat | Mitigation today |
|--------|------------------|
| Customer A reads Customer B tickets | Blocked by `findOwnedTicket` |
| Customer sees internal notes | Blocked by visibility filter |
| Unauthenticated access | Sanctum |
| Customer uses admin shell | Frontend redirect to `/portal` |

## Attachment threat model

| Threat | Mitigation today |
|--------|------------------|
| Direct public URL | Private disk + authenticated download |
| Oversized upload | Extension allow-list + size limit |
| Portal download | **Gap** — cannot retrieve after upload |

## Approval note

Safe to operate in **trusted staff / known companies** deployments. Block SaaS marketplace / unknown-tenant onboarding until S-01 and S-02 are closed.
