# Incoming webhook example payloads

Ready-to-sign JSON bodies for connecting any website to AMS Support / Compliance.

Use with an **Incoming** webhook secret. Sign the **exact raw file bytes** (or the same JSON string you POST) with HMAC-SHA256 and send header:

```text
X-AMS-Signature: sha256={hex}
```

| File | Intent | AMS destination |
|------|--------|-----------------|
| [support-help.json](./support-help.json) | Help / login issue | Support only |
| [support-complaint.json](./support-complaint.json) | Complaint form | Support only |
| [compliance-privacy.json](./compliance-privacy.json) | GDPR / delete health data | Support + Privacy Request |
| [support-disable-account.json](./support-disable-account.json) | Disable account | Support only |
| [support-sms.json](./support-sms.json) | Inbound SMS | Support only (`source=sms`) |
| [compliance-case.json](./compliance-case.json) | Compliance case | **Compliance Cases only** (no Support) |
| [compliance-breach.json](./compliance-breach.json) | Data breach report | **Breaches only** (no Support) |
| [compliance-consent.json](./compliance-consent.json) | Consent withdrawal | **Privacy Request only** (no Support) |
| [compliance-dpia.json](./compliance-dpia.json) | DPIA request | **DPIA only** (no Support) |

Full guide: [Connect Website → Support + Compliance](../Connect-Website-Support-Compliance.md)

Replace `application_slug` (`my-shop-web`) and regenerate `message_id` for each real request so idempotency does not skip ingest.
