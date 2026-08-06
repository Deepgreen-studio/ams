# Customer Management — User Guide

For AMS administrators and account managers.

## Access

1. Sign in to the admin portal.
2. Open **Customers** in the sidebar.
3. You need `customers.view` (and create/update/delete/restore as required). API enforces permissions even if a UI control is visible.

## Customer list

- Search by name/email and filter by company, type, status, and archived records.
- Open a row to view the customer hub.
- Use **Create customer** for individual, business, or enterprise profiles.

## Customer hub

From the customer detail page you can open:

| Tile | What you do |
|------|-------------|
| Contacts | Manage relationship owners (keep one Primary) |
| Applications | Assign AMS applications, environments, integrations |
| Subscriptions | Create plans, track payment status, cancel |
| Licenses | Issue/edit keys, revoke, review history |
| Documents | Upload contracts/NDAs/invoices; upload new versions |
| Communications | Notes, tasks/reminders, email & call logs, calendar |
| Analytics | Health score, risk, usage charts, timeline |

## Contacts

1. Open **Contacts** → **Add contact**.
2. Set type (Primary, Technical, Billing, Support, Emergency).
3. Marking a contact Primary demotes any previous Primary.

## Applications

1. **Assign application** — pick an application belonging to the same company.
2. Optionally set environment, integration, owner contact, activation/expiry.
3. Use **History** for prior assignments and archived rows.

## Subscriptions & licenses

1. Create a subscription (trial plans default to trialing).
2. Create or attach licenses under the customer (and optionally link the assignment).
3. Cancel subscription or revoke a license when access must end.
4. Dashboard surfaces renewal reminders when configured reminder windows apply.

Billing today uses the **manual** gateway. Stripe endpoints are prepared but not live.

## Documents

1. Browse category folders on the left.
2. Upload a file (allowed: PDF, Office, images, common archives; max 50 MB).
3. Open a document to preview/download or upload a **new version** (previous versions remain in history).

## Communication center

Tabs:

- **Timeline** — merged notes, tasks, and communications
- **Notes** — general / internal / meeting
- **Tasks** — priorities, due dates, reminders; mark complete
- **Calendar** — due and scheduled items
- **Emails / Calls** — logged communications

## Analytics

1. Open **Analytics** on the customer hub.
2. Review health & activity scores, risk indicators, and charts.
3. Click **Refresh snapshot** to recompute today’s metrics.

**Note:** Support ticket counts are temporary proxies (tasks + email/call logs) until the Support module ships. API usage comes from assigned application session analytics.

## Archive & restore

Most customer resources soft-delete (Archive). Use trash/include-archived filters where available, then **Restore** when needed.
