# Administrator Guide — Compliance

## Scope

Configure and operate the full Compliance suite: cases, privacy, consent, breaches, DPIA/risk, policies, analytics.

## Roles & permissions

Assign Spatie permissions (see Roles module):

| Permission | Typical role |
|------------|--------------|
| `compliance.view` | Read-only auditors |
| `compliance.create` | Intake staff |
| `compliance.update` | Officers handling workflows |
| `compliance.delete` | Senior officers |
| `compliance.manage` | Compliance admin / DPO (elevated) |

**Note:** Permissions are coarse today. Approving DPIAs, publishing policies, and closing breaches share `compliance.update`. Prefer limiting update access until finer SoD permissions ship.

## Day-2 operations

### Cases
Use Cases Dashboard for open/elevated work. Assign owners and track status through completion.

### Privacy
See [Privacy-Guide.md](./Privacy-Guide.md). Watch overdue DSARs weekly.

### Consent
- Keep consent types accurate (names, versions, required flags).  
- Use Preference Center for subject preference updates.  
- Use Consent Audit / History for disputes.

### Data breaches
1. Report immediately with accurate `discovered_at`.  
2. Complete risk assessment and containment.  
3. Track regulator deadline (72h).  
4. Record notifications before closing when regulator notify is required.  
5. Maintain affected-user counts/lists.

### DPIA & risk
- Run wizard for high-risk processing.  
- Register risks, score likelihood × impact, track mitigations.  
- Approve or reject with notes.

### Policies
- Never edit published text “in place” — saves create new versions.  
- Use Approval Queue for legal review.  
- Publish only after approval.  
- Link CMS content when public pages are managed in Content module.

### Analytics
- Filter by date range.  
- Export CSV/Excel for board packs.  
- PDF export is marked architecture-ready (print UI as interim).

## Company scoping (current behavior)

Lists accept optional company filters. Authorization does **not** yet hard-scope users to a single company. For multi-company deployments, restrict who receives `compliance.*` until tenant isolation is enforced in policies.

## Audit

All major actions emit `compliance` activity logs. Use Analytics → Audit Reports or the platform Audit module.

## Production checklist

- [ ] Seed roles/permissions applied  
- [ ] Consent types reviewed  
- [ ] Privacy / security policies published  
- [ ] Breach on-call process documented (outside AMS)  
- [ ] Operators trained that deletion confirm ≠ automated purge  
- [ ] Queue/workers running for notifications  
