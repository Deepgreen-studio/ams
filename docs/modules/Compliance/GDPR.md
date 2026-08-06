# GDPR Documentation

How AMS Compliance supports GDPR operational controls. This is an **operational platform guide**, not legal advice.

## Principles mapped

| GDPR theme | AMS capability |
|------------|----------------|
| Lawfulness & transparency | Policy documents (privacy/terms/cookie/security) with version history |
| Consent | Consent types, grant/withdraw, preference center, immutable history |
| Subject access / portability | Privacy requests: access, export, portability workflows |
| Rectification / restriction / objection | Privacy request types + case tracking |
| Erasure (“right to be forgotten”) | Deletion-type DSAR with **confirmation workflow** (purge pipeline backlog) |
| Security of processing | Security policy docs, breach module, DPIA & risk register |
| Breach notification | Data breach cases with **72-hour regulator deadline** tracking |
| DPIA | Wizard assessments + risk scoring + mitigation actions |
| Accountability / audit | Activity log (`compliance`), privacy/consent timelines, analytics audit report |

## Privacy request (DSAR) lifecycle

1. **Submitted** — intake with company, type, subject identifiers  
2. **Identity pending / verified** — staff verification gate  
3. **Under review → Approved / Rejected**  
4. **In progress** — export package and/or deletion confirmation  
5. **Completed / Cancelled**  

Default due date targets **30 days** from creation (operator-configurable per record).

### Export / portability

Approved export/portability flows generate a JSON package stored on the local disk and downloadable via API. Current package includes subject + linked customer/company profile fields — **not** a full platform subject graph (tickets, devices, full activity, etc.). Expansion is a Phase 8+ backlog item.

### Erasure status

`confirmDeletion` records `deletion_confirmed_at` and timeline evidence. It does **not** yet anonymize or purge related AMS entities. Treat current behavior as **case governance**; wire a real erasure pipeline before claiming automated RtbF fulfillment.

## Consent

- Platform consent types are seeded (marketing, analytics, push, email, SMS, cookies, etc.).  
- Grant / withdraw creates `user_consents` and append-only `consent_history`.  
- Preference center supports bulk preference save for a subject.  
- Captures IP, user agent, device, and source when provided.

## Personal data breaches

- Intake with severity, discovery time, affected systems/users.  
- Risk assessment, containment, recovery, notification records.  
- Regulator deadline: `discovered_at + 72 hours` → `regulator_deadline_at`.  
- Closing is blocked when regulator notification is required but not marked sent.  
- “Send notification” today is **bookkeeping** (status + timestamp), not an automatic regulator gateway.

## DPIA

- Template-driven wizard, submit → approve/reject.  
- Linked **risk register** with likelihood × impact scoring and mitigation actions.  
- Residual risk and mitigation summaries on the assessment.

## Policy governance

- Types: privacy, terms, cookie, security, internal, employee handbook, compliance document.  
- Every content change creates a **new immutable** `policy_versions` row.  
- Approval queue: Draft → Review → Approved → Published → Archived.  
- Restore previous version **appends** a new version (never rewrites history).  
- Optional link to CMS content version history.

## Analytics & accountability

Cross-module KPIs and reports (GDPR, consent, audit, risk charts) with CSV/Excel export. PDF export is architecture-ready.

## Gaps vs enterprise GDPR platforms

| Gap | Severity | Notes |
|-----|----------|-------|
| Real erasure / anonymization pipeline | Critical | Confirmation only today |
| Full subject data graph for export | High | Shallow package |
| Tenant isolation in authorization | High | Permission-only; company not enforced in Gate policies |
| Separation of duties (DPO vs operator) | High | Coarse `compliance.*` permissions |
| Outbound regulator/customer breach channels | High | Bookkeeping only |
| Subject self-service portal | Medium | Admin-operated |
| RoPA / processor register / legal hold | Medium–High | Not in Phase 7 |

## References

- Privacy operators: [Privacy-Guide.md](./Privacy-Guide.md)  
- Admins: [Administrator-Guide.md](./Administrator-Guide.md)  
- Validation report: [reviews/Compliance-Report.md](./reviews/Compliance-Report.md)  
