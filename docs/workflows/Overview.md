# Workflows Domain — Phase 8.4 Enterprise Workflow Engine

## Overview

Enterprise Workflow Engine for AMS. Supports approval, business, sequential, parallel, and custom workflows with a visual designer, approval queue, monitor, timeline, and history.

## Responsibilities

- Define reusable workflow graphs (stages / steps)
- Start workflow instances against any subject
- Evaluate conditions, route approvals / rejections
- Enforce timeouts with optional escalation
- Persist full audit trail in `workflow_logs`

## Workflow Types

| Type | Description |
|------|-------------|
| `approval` | Approval / rejection oriented flows |
| `business` | General business process flows |
| `sequential` | Ordered stage progression |
| `parallel` | Parallel gateway branching |
| `custom` | Free-form designer graphs |

## Step Types

| Step | Purpose |
|------|---------|
| `start` | Entry point (auto-advances) |
| `approval` | Approvers, timeout, escalation |
| `task` | Business stage |
| `condition` | Conditional routing |
| `parallel_gateway` | Split into parallel branches |
| `end` | Terminal outcome |

## Tables

- `workflows`
- `workflow_steps`
- `workflow_instances`
- `workflow_logs`

## API (prefix `/api/v1/workflows`)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboard` | Definition + instance statistics |
| GET | `/catalog` | Types / statuses / step types |
| GET | `/` | List definitions |
| POST | `/` | Create definition + steps |
| GET | `/{uuid}` | Definition detail |
| PUT | `/{uuid}` | Update definition + steps |
| DELETE | `/{uuid}` | Soft-delete |
| POST | `/{uuid}/toggle` | Enable / disable |
| POST | `/{uuid}/publish` | Activate definition |
| POST | `/{uuid}/archive` | Archive definition |
| POST | `/{uuid}/start` | Start instance |
| GET | `/monitor` | Monitor dashboard |
| GET | `/queue` | Approval queue |
| GET | `/history` | Global logs |
| GET | `/instances` | Instance list |
| GET | `/instances/{uuid}` | Instance + timeline |
| POST | `/instances/{uuid}/approve` | Approve |
| POST | `/instances/{uuid}/reject` | Reject |
| POST | `/instances/{uuid}/cancel` | Cancel |

## Permissions

- `workflows.view`
- `workflows.create`
- `workflows.update`
- `workflows.delete`
- `workflows.manage`
- `workflows.approve`

## Scheduler

`workflows:process-timeouts` runs every minute for timeout + escalation processing.

## Frontend

- Dashboard
- Drag & drop Workflow Designer
- Monitor
- Approval Queue
- Instance timeline
- History

## Testing Notes

- Feature tests: `tests/Feature/Workflows/WorkflowEngineTest.php`
- Seed sample definitions: `WorkflowSeeder`
