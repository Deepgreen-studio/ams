# Customer Management — Database Documentation

**Domain:** Customers (Phases 4.1–4.7)  
**Engine:** MySQL 8+ (SQLite in tests)

## Entity Relationship (logical)

```
companies
  └── customers
        ├── customer_contacts
        ├── customer_applications ──► applications / environments / integrations
        ├── subscriptions ──► licenses
        ├── customer_documents (versioned via document_group_uuid)
        ├── customer_notes
        ├── customer_tasks
        ├── customer_communications
        └── customer_analytics_snapshots (daily unique per customer)
```

## Tables

### `customers`

| Column | Notes |
|--------|--------|
| uuid | Unique public identifier |
| company_id | FK → companies CASCADE |
| customer_type | individual / business / enterprise |
| first_name, last_name, company_name | Profile |
| email | Unique per company (app rule) |
| phone, website, industry, country | Optional |
| timezone, language | Defaults UTC / en |
| status | active / inactive / etc. |
| notes | Free text |
| created_by, updated_by | FK → users nullOnDelete |
| deleted_at | Soft delete |

Indexes: company+email/status/type, country, creators.

### `customer_contacts`

FK `customer_id` CASCADE. Types: primary, technical, billing, support, emergency. Soft deletes. Only one primary contact enforced in service layer.

### `customer_applications`

FKs: `customer_id`, `application_id`, optional `application_environment_id`, `integration_id`, `owner_contact_id`. Ownership type + status + activate/expire dates. Soft deletes. Duplicate assignment rejected at service layer. Cross-company application assignment rejected.

### `subscriptions`

FK `customer_id`, optional `customer_application_id`. Plan type/name, status, payment_status/provider, external Stripe-ready IDs, amount/currency, renewal dates, features JSON, soft deletes.

### `licenses`

FKs: `subscription_id`, `customer_id`, optional `customer_application_id`. Unique `license_key`, activation limits/counts, revoke metadata, soft deletes.

### `customer_documents`

FK `customer_id`. Versioning: `document_group_uuid` + `version` + `is_current`. Category acts as virtual folder. Storage: `disk`, `path`, mime/size. Soft deletes.

### `customer_notes` / `customer_tasks` / `customer_communications`

All FK `customer_id` CASCADE, soft deletes, audit user FKs.  
Tasks: status, priority, due_at, remind_at, assigned_to.  
Communications: type, direction, participants JSON, occurred_at.

### `customer_analytics_snapshots`

FK `customer_id` CASCADE. Unique `(customer_id, snapshot_date)`. Counters + health/activity scores + risk_level + metrics JSON. **No soft deletes** (time-series upserts).

## Migrations

| File | Tables |
|------|--------|
| `2026_08_03_210000_create_customers_table.php` | customers |
| `2026_08_03_220000_create_customer_contacts_table.php` | customer_contacts |
| `2026_08_03_230000_create_customer_applications_table.php` | customer_applications |
| `2026_08_03_240000_create_subscriptions_and_licenses_tables.php` | subscriptions, licenses |
| `2026_08_03_250000_create_customer_documents_table.php` | customer_documents |
| `2026_08_03_260000_create_customer_communication_tables.php` | notes, tasks, communications |
| `2026_08_03_270000_create_customer_analytics_snapshots_table.php` | analytics snapshots |

## Factories

All primary models have factories under `backend/database/factories/Customer*.php`, `SubscriptionFactory.php`, `LicenseFactory.php`.

## Data integrity notes

- Prefer soft delete over hard delete for recovery and audit continuity.
- Document versions keep prior rows with `is_current = false`.
- Analytics metrics.document sources may note proxies until Support module exists.
