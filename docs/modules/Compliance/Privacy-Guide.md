# Privacy Guide

Operational guide for handling **Privacy Requests (DSAR)** in AMS.

## Who this is for

Privacy officers, compliance officers, and administrators with `compliance.view` / `compliance.update`.

## Access

UI: **Compliance → Privacy Dashboard / Privacy Requests**  
API: `/api/v1/compliance/privacy-requests`

## Request types

| Type | Typical outcome |
|------|-----------------|
| Access | Provide subject information summary |
| Export / Portability | Generate downloadable JSON package |
| Correction | Track correction fulfillment via case notes/workflow |
| Deletion | Confirm deletion after approval |
| Restrict / Object / Consent withdrawal | Track and complete per policy |

## Standard workflow

1. **Create** request (company, type, subject name/email, optional customer link).  
2. **Verify identity** before approving sensitive fulfillment.  
3. **Approve** or **Reject** (rejection requires notes).  
4. For export types: **Generate export** → **Download**.  
5. For deletion types: **Confirm deletion** (records confirmation timestamp and audit log).  
6. **Complete** the request.

## SLAs

- Default due date: ~30 days from creation.  
- Monitor Privacy Dashboard for open and overdue items.  
- All status changes are written to `privacy_request_logs` and activity log.

## Important limitations (operators must know)

- **Deletion confirmation is not automatic data purge.** Confirm only after operational erasure is completed outside or when the purge pipeline is implemented.  
- **Export packages are scoped** to privacy-request payload fields (subject + customer/company profile), not every AMS subsystem.  
- Do not share export downloads outside authorized channels.

## Related cases

Escalate complex matters to **Compliance Cases**. Link context in descriptions/notes.

## Permissions checklist

| Action | Permission |
|--------|------------|
| View / dashboard | `compliance.view` |
| Create | `compliance.create` |
| Verify / approve / export / confirm / complete | `compliance.update` (or manage) |
| Soft delete | `compliance.delete` |
