# Application Management — Release Documentation

## Scope

Enterprise release planning for an application, always linked to an existing `application_version`.

## States

### Deployment status (`status`)
`planned` → `scheduled` → `pending_approval` → `approved` → `deployed`  
Also: `rejected`, `deploying`, `failed`, `rolled_back`, `cancelled`

### Approval (`approval_status`)
`not_required` | `pending` | `approved` | `rejected`

### Rollback (`rollback_status`)
`none` | `pending` | `in_progress` | `completed` | `failed`

## Lifecycle

1. **Plan** — `POST /releases` with `application_version_id`, type, notes; default requires approval.
2. **Schedule** — `POST /{release}/schedule` sets `scheduled_at`, status `scheduled`.
3. **Submit / Approve / Reject** — approval gates deployment.
4. **Deploy** — records `deployment_date` / `deployed_at`; blocked if approval pending.
5. **Rollback** — only from `deployed`; may create a linked rollback release (`release_type=rollback`).

## Stored fields

Version label (denormalized), release type, plan summary, metadata JSON, approved_by / rolled_back_by, optional environment, nested release notes (`audience`: public|internal|both).

## Events

`ApplicationReleaseCreated|Updated|Deleted|Approved|Rejected|Deployed|RolledBack` → activity log.

## Frontend surfaces

- Release Dashboard
- Plan Release
- Calendar / Timeline
- Details (deploy / rollback actions)
- Approval Screen

## Operational constraints

- Deployed / rolled-back releases are not editable (create a new release).
- Approval currently uses `applications.update` (no dedicated approver permission yet — see Review Reports).
- Releases do not trigger app store publish or CI deploy; status tracking only.
