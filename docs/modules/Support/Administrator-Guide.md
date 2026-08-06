# Support Administrator Guide

For platform admins and support managers (`support.manage`).

## Setup checklist

1. Seed roles/permissions (`RolesAndPermissionsSeeder`)  
2. Seed SLA defaults (`SupportSlaSeeder`)  
3. Seed knowledge categories (`KnowledgeBaseSeeder`)  
4. Seed canned responses (`SupportCannedResponseSeeder`)  
5. Seed notification templates (`NotificationTemplateSeeder`)  
6. Optional portal demo user (`PortalCustomerUserSeeder`)  
7. Confirm schedule: `support:evaluate-sla` every 5 minutes  
8. Configure mail for notification delivery (queue `notifications`)  
9. Set `support_attachments_disk` to a **private** disk in production  

## SLA configuration

1. **Calendars** — business hours + timezone (`/support/sla/calendars`)  
2. **Holidays** — non-working dates  
3. **Policies** — response / resolution targets; global default + optional company override  
4. **Escalation rules** — levels `level_1` → `administrator`  
5. Monitor **Escalations** and **Violations** queues  

SLA pauses when ticket status is **Waiting for Customer**.

## Notifications

1. Review templates at `/notifications/templates`  
2. Confirm global toggles in Settings → notifications (`email_enabled`, `in_app_enabled`)  
3. Train agents to set preferences at `/notifications/preferences`  
4. Audit delivery at `/notifications/delivery-logs`  

Recipients by default: ticket assignee + users with `support.manage` + portal users linked to the ticket’s customer (excluding the actor).

## Knowledge Base

- Own taxonomy (categories/tags) independently of CMS  
- Link important CMS help articles via `link-cms` when content is mastered in CMS  
- Feature evergreen FAQs  

## Canned responses

- Agents create **personal** templates  
- Only managers create **shared** team templates  
- Insert from ticket reply composer  

## Customer portal

1. Create CRM **Customer** under a company  
2. Create **User**, assign role `customer`, set `customer_id`  
3. Customer logs in → redirected to `/portal/tickets`  
4. Portal tickets use `source=portal` and auto-bind company/customer  

Demo: `portal.customer@example.com` / `Password123!` (after seeder).

## Security responsibilities

- Prefer scoped company filters when browsing tickets in multi-company environments (admin list can show all companies today — see Security Report S-01)  
- Do not expose private attachment disks publicly  
- Restrict `support.manage` to trusted staff  

## Operations

| Command | Purpose |
|---------|---------|
| `php artisan support:evaluate-sla` | Evaluate open ticket SLAs / escalations |
| `php artisan queue:work --queue=notifications` | Process notification jobs |

## Frontend admin map

| Area | Path |
|------|------|
| Support Center | `/support` |
| Tickets / Kanban / Queue / Assignment | `/support/tickets…` |
| SLA | `/support/sla…` |
| Knowledge | `/support/knowledge…` |
| Canned | `/support/canned-responses` |
| Notifications | `/notifications…` |
