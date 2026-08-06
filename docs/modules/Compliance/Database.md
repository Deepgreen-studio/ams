# Compliance Database

MySQL 8+ schema for Phases 7.1–7.7. All primary entities use UUID + soft deletes unless noted.

## Tables

| Table | Purpose |
|-------|---------|
| `compliance_cases` | General compliance cases |
| `privacy_requests` | DSAR / privacy rights requests |
| `privacy_request_logs` | Privacy timeline |
| `consent_types` | Consent type catalog |
| `user_consents` | Subject consent records |
| `consent_history` | Append-only consent changes |
| `data_breaches` | Breach incidents |
| `breach_actions` | Breach action log |
| `breach_notifications` | Notification records |
| `dpia_assessments` | DPIA assessments |
| `risk_register` | Risks (optionally linked to DPIA) |
| `risk_actions` | Mitigation actions |
| `policies` | Policy documents (`PolicyDocument` model) |
| `policy_versions` | Immutable policy snapshots |
| `policy_approvals` | Policy approval workflow |

## Relationships (high level)

```
companies 1—* compliance_cases | privacy_requests | user_consents | data_breaches | dpia_assessments | risk_register | policies
privacy_requests 1—* privacy_request_logs
consent_types 1—* user_consents
user_consents 1—* consent_history
data_breaches 1—* breach_actions | breach_notifications
dpia_assessments 1—* risk_register
risk_register 1—* risk_actions
policies 1—* policy_versions | policy_approvals
policies *—0..1 contents (CMS link)
```

## Indexing notes

- Status, type, due-date, and company indexes exist on primary tables.  
- Analytics date-range queries benefit from future composites: `(company_id, created_at)`.  
- Breach regulator deadline columns are indexed for queue views.

## Immutability

- `policy_versions`: no `updated_at`; created via append-only service API.  
- `consent_history` / `privacy_request_logs`: append-oriented.  
- Application-level immutability — not DB triggers.

## Soft delete vs cascade

Hard-deleting parents with `cascadeOnDelete` children can remove audit rows. Prefer soft delete in production operations.
