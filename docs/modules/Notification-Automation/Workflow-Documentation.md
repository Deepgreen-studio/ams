# Workflow Documentation

## Overview

The Enterprise Workflow Engine supports approval, business, sequential, parallel, and custom workflows with a drag-drop designer and runtime monitor.

**Domain:** `backend/app/Domains/Workflows/`  
**Frontend:** `frontend/src/modules/workflows/`  
**API prefix:** `/api/v1/workflows`  
**Command:** `php artisan workflows:process-timeouts` (every minute)

## Workflow types

| Type | Use |
|------|-----|
| `approval` | Human approval gates |
| `business` | Business process flows |
| `sequential` | Ordered steps |
| `parallel` | Parallel branches |
| `custom` | Configurable graphs |

## Lifecycle

1. Design workflow + steps in designer
2. Publish definition
3. Start instance (subject type/id optional)
4. Approve / reject / cancel at pending steps
5. Timeout processor escalates overdue instances

## Frontend surfaces

- Dashboard
- Designer (list / create / edit)
- Monitor
- Approval queue
- History
- Instance timeline

## Database

| Table | Purpose |
|-------|---------|
| `workflows` | Definitions (soft delete) |
| `workflow_steps` | Graph steps + routing keys |
| `workflow_instances` | Runtime instances (status lifecycle) |
| `workflow_logs` | Audit trail of transitions |

## Instance statuses

`pending` · `in_progress` · `approved` · `rejected` · `cancelled` · `timed_out` · `completed`

Success metrics treat `approved` + `completed` as success; `rejected` + `timed_out` + `cancelled` as failures (Platform Analytics).

## Permissions

- `workflows.view`
- `workflows.create`
- `workflows.update`
- `workflows.delete`
- `workflows.manage`
- `workflows.approve`

## Operational requirements

1. Keep `workflows:process-timeouts` scheduled.
2. Ensure approvers in step config match users who hold `workflows.approve`.
3. Content Approvals in the sidebar is a separate Content-domain flow — do not confuse with this engine.

## Related docs

- `docs/workflows/Overview.md`
- Analytics: Workflow Reports under `/analytics/workflows`
