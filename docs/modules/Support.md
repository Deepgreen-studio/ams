# Support Module

## Overview

Enterprise Support & Helpdesk for AMS.

- **Phase 6.1** — Ticket foundation (CRUD, dashboard, filters)
- **Phase 6.2** — Ticket workflow (status lifecycle, priorities, assignment strategies, kanban, queue, timeline)
- **Phase 6.3** — Ticket conversation (public/private/internal messages, attachments, read status)
- **Phase 6.4** — SLA management (response/resolution SLA, escalations, business hours, holidays)
- **Phase 6.5** — Knowledge Base (articles/guides/FAQs/tutorials/videos/release notes + CMS link)
- **Phase 6.6** — Canned responses (personal + shared templates in reply composer)
- **Phase 6.7** — Notifications (email + in-app, preferences, templates, delivery logs)
- **Phase 6.8** — Customer portal ticket intake + Support Module Review (docs & readiness reports)

See index: [Support/README.md](./Support/README.md).

## Workflow Statuses

- Open
- Pending
- In Progress
- Waiting for Customer
- Resolved
- Closed
- Reopened
- Cancelled

Transitions are enforced server-side via `SupportTicketStatus::allowedTransitions()`.

## Priorities

- Low
- Medium
- High
- Critical
- Emergency

Emergency support category auto-raises low/medium/high to **Emergency**.

## Assignment Types

| Type | Behavior |
|------|----------|
| Manual | Assign a selected agent |
| Auto | Round-robin among `support-agent` / `support-manager` |
| Department | Assign to a department (no agent yet) |
| Team | Assign to a team (manager becomes assignee when present) |
| Agent | Assign a specific agent |

## Conversation

### Message visibility

| Visibility | Purpose |
|------------|---------|
| `public` | Customer-visible reply |
| `private` | Private reply (restricted audience) |
| `internal` | Internal agent note |

### Attachments

Stored on the **private** disk configured by `filesystems.support_attachments_disk` (default `local`).

Types: `file`, `screenshot`, `video`, `document` (auto-detected from extension).

Access only via authenticated download/preview endpoints (no public URLs).

### Read status

Tracked in `ticket_message_reads` per user. Authors are treated as already read for their own messages.

## SLA Management

### Scope

- Global default policies
- Optional per-company overrides (higher precedence)

### Metrics

- **Response SLA** — first public/private agent reply
- **Resolution SLA** — ticket resolved/closed

### Business hours

- `support_sla_calendars` — weekday time windows + timezone
- `support_sla_holidays` — date exceptions (optional yearly recurrence)
- Policies may use business-hours clocks or wall-clock time

### Escalation levels

Level 1 → Level 2 → Level 3 → Manager → Administrator

Triggers: `response_at_risk`, `response_breached`, `resolution_at_risk`, `resolution_breached`

### Ticket SLA fields

`sla_status`, `escalation_level`, due/completed/breach timestamps, pause tracking

Statuses: `on_track`, `at_risk`, `breached`, `paused`, `met`, `not_applicable`

Waiting for customer pauses the SLA clock.

### Scheduler

`php artisan support:evaluate-sla` runs every 5 minutes.

## Folder Structure

```
backend/app/Domains/Support/
  Console/
  Controllers/
  Enums/
  Events/
  Jobs/
  Listeners/
  Models/
  Notifications/
  Policies/
  Repositories/
  Requests/
  Resources/
  Routes/
  Services/

frontend/src/modules/support/
  components/
  pages/
  services/
  stores/
  utils/
```

## Database Tables

- `support_tickets` (+ SLA columns)
- `support_ticket_status_histories`
- `ticket_messages`
- `ticket_attachments`
- `ticket_message_reads`
- `support_sla_calendars`
- `support_sla_holidays`
- `support_sla_policies`
- `support_sla_escalation_rules`
- `support_sla_escalations`

## API Endpoints

### Tickets / workflow / conversation

See phases 6.1–6.3 endpoints under `/api/v1/support/tickets/*`.

### SLA

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/support/sla/dashboard` | SLA stats + timers |
| GET | `/api/v1/support/sla/escalations` | Escalation queue |
| POST | `/api/v1/support/sla/escalations/{id}/acknowledge` | Acknowledge |
| POST | `/api/v1/support/sla/escalations/{id}/resolve` | Resolve |
| GET | `/api/v1/support/sla/violations` | Violation report |
| POST | `/api/v1/support/sla/evaluate` | Force evaluation |
| GET/POST | `/api/v1/support/sla/policies` | List/create policies |
| GET/PUT/DELETE | `/api/v1/support/sla/policies/{id}` | Policy CRUD |
| GET/POST | `/api/v1/support/sla/calendars` | Business hours |
| PUT | `/api/v1/support/sla/calendars/{id}` | Update calendar |
| GET/POST | `/api/v1/support/sla/holidays` | Holiday calendar |
| PUT/DELETE | `/api/v1/support/sla/holidays/{id}` | Holiday update/delete |

## Knowledge Base

Support owns help-center indexing, feedback, categories/tags, and version history.

Optional CMS connection via `knowledge_articles.content_id` → `contents.id`.

When `sync_from_cms` is true, title/body/featured image are pulled from the linked CMS entry.

### Types

`article`, `guide`, `faq`, `tutorial`, `video`, `release_notes`

### Tables

- `knowledge_articles`
- `knowledge_categories`
- `knowledge_tags`
- `knowledge_article_tag`
- `knowledge_article_versions`
- `knowledge_article_feedback`
- `knowledge_article_relations`

### Knowledge API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/support/knowledge/dashboard` | Knowledge center |
| GET/POST | `/api/v1/support/knowledge/articles` | List / create |
| GET/PUT/DELETE | `/api/v1/support/knowledge/articles/{id}` | Show / update / delete |
| POST | `.../publish` · `.../archive` | Lifecycle |
| POST | `.../link-cms` · `.../unlink-cms` | CMS connection |
| GET | `.../versions` | Version history |
| POST | `.../versions/{version}/restore` | Restore version |
| POST | `.../feedback` | Helpful / not helpful |
| GET/POST | `/api/v1/support/knowledge/categories` | Categories |
| GET/POST | `/api/v1/support/knowledge/tags` | Tags |

## Canned Responses

Personal templates are visible only to the owner. Shared templates are visible to all agents with `support.view`.

Creating/updating shared templates requires `support.manage`. Owners can manage their own personal templates.

### Table

- `support_canned_responses`

### Canned Response API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/support/canned-responses/dashboard` | Stats + popular |
| GET/POST | `/api/v1/support/canned-responses` | List / create |
| GET/PUT/DELETE | `/api/v1/support/canned-responses/{id}` | Show / update / delete |
| POST | `/api/v1/support/canned-responses/{id}/use` | Increment usage on insert |

## Permissions

- `support.view`
- `support.create`
- `support.update`
- `support.delete`
- `support.manage` (policy/calendar/category/tag/shared canned admin + evaluate)

## Frontend Routes

- `/support` — Dashboard
- `/support/tickets` — Ticket list
- `/support/tickets/board` — Kanban
- `/support/tickets/queue` — Queue
- `/support/tickets/assignment` — Assignment
- `/support/sla` — SLA dashboard / timers
- `/support/sla/escalations` — Escalation queue
- `/support/sla/violations` — Violation reports
- `/support/sla/policies` — Policies
- `/support/sla/calendars` — Business hours & holidays
- `/support/knowledge` — Knowledge Center
- `/support/knowledge/articles` — Search/browse
- `/support/knowledge/articles/create` — Create
- `/support/knowledge/articles/:id` — Viewer + feedback + versions
- `/support/knowledge/articles/:id/edit` — Edit
- `/support/canned-responses` — Manage personal & shared templates
- `/support/tickets/create` — Create ticket
- `/support/tickets/:id` — Ticket details + conversation + SLA timers + insert canned

## Events

- SupportTicketCreated / Updated / Deleted / Restored
- SupportTicketAssigned / Closed / Reopened / StatusChanged
- SupportTicketMessageCreated / AttachmentUploaded
- SupportTicketSlaBreached
- SupportTicketSlaEscalated

## Testing Notes

- `tests/Feature/Support/SupportTicketManagementTest.php`
- `tests/Feature/Support/SupportTicketWorkflowTest.php`
- `tests/Feature/Support/SupportTicketConversationTest.php`
- `tests/Feature/Support/SupportSlaManagementTest.php`
- `tests/Feature/Support/KnowledgeBaseTest.php`
- `tests/Feature/Support/SupportCannedResponseTest.php`
- Seeders: `SupportSlaSeeder`, `KnowledgeBaseSeeder`, `SupportCannedResponseSeeder`

## Remaining Tasks (Next Phases)

- SMS / Push channel drivers
- Real-time bell (websockets / broadcasting)
- Portal application picker / knowledge self-service