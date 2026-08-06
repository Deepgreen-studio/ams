# Security Report — Integration Hub (Phase 2.8)

**Verdict:** Strong baseline (authn/z, encryption at rest for secrets, signature verification, throttling, secret masking). Address SSRF and production hardening items before Internet-exposed hardening sign-off.

## Controls in place

| Control | Status | Notes |
|---------|--------|-------|
| Sanctum auth on admin APIs | Pass | `auth:sanctum` group |
| Spatie permissions | Pass | Route middleware + policies |
| Encrypted integration credentials | Pass | `encrypted:array` on `Integration` |
| Encrypted webhook secrets | Pass | `encrypted` cast on `Webhook` |
| Timing-safe signature compare | Pass | `hash_equals` in `SignatureValidator` |
| Incoming webhook throttle | Pass | `throttle:webhook-incoming` |
| API throttle | Pass | `throttle:api` |
| Authorization header masking in history | Pass | Covered by Feature test |
| Soft deletes on integrations | Pass | Restore permission gated |

## Findings

### High

| ID | Finding | Recommendation |
|----|---------|----------------|
| SEC-01 | Outbound URL SSRF risk — operators can point request tester / base URLs at internal hosts (`localhost`, link-local, cloud metadata) | Add URL validation in `ApiClientService` / `ConnectionManager`: block private ranges, optional company allowlist of hostnames |

### Medium

| ID | Finding | Recommendation |
|----|---------|----------------|
| SEC-02 | Signature algorithm `none` is accepted | Disallow for production webhook create/update unless `APP_ENV=local`; audit existing rows |
| SEC-03 | `POST /queue/sample` available in production API surface | Gate behind `APP_DEBUG` or remove from production routes |
| SEC-04 | RateLimitManager increment is get/put (non-atomic) | Use Redis `INCR`/`Cache::increment` with TTL to avoid under-counting under concurrency |

### Low

| ID | Finding | Recommendation |
|----|---------|----------------|
| SEC-05 | Connection/response bodies truncated but may retain PII | Document retention; optional scrubbing hooks |
| SEC-06 | Incoming auth relies solely on shared secret | Consider IP allowlist / mTLS for high-trust partners (future) |

## Threat model (summary)

| Threat | Mitigation today | Residual |
|--------|------------------|----------|
| Stolen admin token | Sanctum + permissions | Session/device management future |
| Forged incoming webhook | HMAC + throttle | Algorithm `none`, no IP allowlist |
| Credential leakage in logs | Encryption + header mask | Body PII |
| Abuse of request tester | Permission `manage` | SSRF internal pivot |
| Queue overload | Priority queues / tries | DB queue weaker than Redis |

## Score

**Security readiness: 76 / 100** (staging OK; harden SSRF + sample endpoint before broad production exposure)
