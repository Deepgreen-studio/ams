# Support Module — API Reference

**Phase:** 6.1–6.8  
**Base URL:** `/api/v1`  
**Auth:** Laravel Sanctum (`Authorization: Bearer {token}` or SPA cookie)  
**Envelope:**

```json
{ "success": true, "message": "", "data": {} }
```

Errors: `{ "success": false, "message": "...", "errors": {} }` (validation).

Permissions: `support.view|create|update|delete|manage`, `notifications.*`.

---

## Admin — Tickets

| Method | Path | Permission | Description |
|--------|------|------------|-------------|
| GET | `/support/dashboard` | view | Stats, recent, urgent |
| GET | `/support/agents` | view | Assignable agents |
| GET | `/support/tickets` | view | Paginated list + filters |
| GET | `/support/tickets/board` | view | Kanban columns |
| GET | `/support/tickets/queue` | view | Unassigned / critical |
| POST | `/support/tickets` | create | Create ticket |
| GET | `/support/tickets/{uuid}` | view | Ticket detail |
| PUT | `/support/tickets/{uuid}` | update/manage | Update |
| POST | `/support/tickets/{uuid}/transition` | update/manage | Status change |
| POST | `/support/tickets/{uuid}/assign` | update/manage | Assign |
| POST | `/support/tickets/{uuid}/close` | update/manage | Close |
| POST | `/support/tickets/{uuid}/reopen` | update/manage | Reopen |
| DELETE | `/support/tickets/{uuid}` | delete/manage | Soft delete |
| POST | `/support/tickets/{uuid}/restore` | delete/manage | Restore |
| GET | `/support/tickets/{uuid}/timeline` | view | Status history |

### Conversation & attachments

| Method | Path | Permission |
|--------|------|------------|
| GET | `/support/tickets/{uuid}/messages` | view |
| POST | `/support/tickets/{uuid}/messages` | create/update/manage |
| POST | `/support/tickets/{uuid}/messages/read` | view |
| GET | `.../attachments/{uuid}/download` | view |
| GET | `.../attachments/{uuid}/preview` | view |
| DELETE | `.../attachments/{uuid}` | update/manage |

Visibility: `public` | `private` | `internal`.

---

## Admin — SLA

| Method | Path | Permission |
|--------|------|------------|
| GET | `/support/sla/dashboard` | view |
| GET | `/support/sla/escalations` | view |
| POST | `/support/sla/escalations/{id}/acknowledge` | update/manage |
| POST | `/support/sla/escalations/{id}/resolve` | update/manage |
| GET | `/support/sla/violations` | view |
| POST | `/support/sla/evaluate` | manage |
| CRUD | `/support/sla/policies` | view / manage |
| CRUD | `/support/sla/calendars` | view / manage |
| CRUD | `/support/sla/holidays` | view / manage |

Scheduler: `support:evaluate-sla` every 5 minutes.

---

## Admin — Knowledge Base

| Method | Path | Permission |
|--------|------|------------|
| GET | `/support/knowledge/dashboard` | view |
| CRUD | `/support/knowledge/articles` | view / create / update / delete |
| POST | `.../publish`, `.../archive` | update/manage |
| POST | `.../link-cms`, `.../unlink-cms` | update/manage |
| GET | `.../versions` | view |
| POST | `.../versions/{id}/restore` | update/manage |
| POST | `.../feedback` | view |
| CRUD | `/support/knowledge/categories` | view / manage |
| CRUD | `/support/knowledge/tags` | view / manage |

Types: `article`, `guide`, `faq`, `tutorial`, `video`, `release_notes`.

---

## Admin — Canned Responses

| Method | Path | Permission |
|--------|------|------------|
| GET | `/support/canned-responses/dashboard` | view |
| GET/POST | `/support/canned-responses` | view / create\|manage |
| GET/PUT/DELETE | `/support/canned-responses/{uuid}` | view / update\|manage / delete\|update\|manage |
| POST | `/support/canned-responses/{uuid}/use` | view |

Visibility: `personal` (owner) | `shared` (requires manage to create).

---

## Customer Portal

Requires: authenticated user with `customer` role **and** `users.customer_id` linked.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/portal/me` | Profile + enums |
| GET | `/portal/support/tickets` | My tickets |
| POST | `/portal/support/tickets` | Submit (`source=portal`) |
| GET | `/portal/support/tickets/{uuid}` | Owned ticket only |
| GET | `/portal/support/tickets/{uuid}/messages` | **Public** messages only |
| POST | `/portal/support/tickets/{uuid}/messages` | Customer public reply |

---

## Notifications (Support events)

| Method | Path | Notes |
|--------|------|-------|
| GET | `/notifications/center` | Recent + unread |
| GET | `/notifications` | History |
| POST | `/notifications/read-all` | Mark all read |
| POST | `/notifications/{id}/read` | Mark one |
| GET/PUT | `/notifications/preferences` | Per-event channels |
| CRUD | `/notifications/templates` | Admin (`notifications.*`) |
| GET | `/notifications/delivery-logs` | Admin |

Event keys: `support.ticket_created`, `support.ticket_assigned`, `support.reply_added`, `support.status_changed`, `support.ticket_closed`, `support.sla_warning`, `support.escalation`.
