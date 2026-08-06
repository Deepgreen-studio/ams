# Customer Portal

## Overview

Phase **6.8** — Customer Portal ticket intake.

Authenticated customers linked via `users.customer_id` can:

- Submit support tickets
- List their own tickets
- View ticket details
- Reply with public messages only

Staff internal/private messages are never returned to portal users.

## Foundation

| Item | Detail |
|------|--------|
| Link | `users.customer_id` → `customers.id` |
| Role | `customer` (`support.view`, `support.create`) |
| Source | Tickets created with `source=portal` |

## API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/portal/me` | Portal profile + option catalogs |
| GET | `/api/v1/portal/support/tickets` | My tickets |
| POST | `/api/v1/portal/support/tickets` | Submit ticket |
| GET | `/api/v1/portal/support/tickets/{id}` | View owned ticket |
| GET | `/api/v1/portal/support/tickets/{id}/messages` | Public conversation |
| POST | `/api/v1/portal/support/tickets/{id}/messages` | Customer public reply |

## Frontend

- `/portal/tickets` — list
- `/portal/tickets/create` — submit
- `/portal/tickets/:id` — view + reply

Portal users are redirected away from the admin shell after login.

## Demo credentials (seeder)

- Email: `portal.customer@example.com`
- Password: `Password123!`
- Seeder: `PortalCustomerUserSeeder`

## Testing

- `tests/Feature/Support/PortalSupportTicketTest.php`
