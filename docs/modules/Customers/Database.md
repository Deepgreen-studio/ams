# Customer Management — Database Documentation

**Domain:** Customers (Phases 4.1–4.7)  
**Engine:** MySQL 8+ (SQLite in tests)

## Entity Relationship (logical)

```
companies
  └── customers
        ├── customer_contacts
        ├── customer_applications ──► applications / environments / versions / releases
        │         └── support_tickets.customer_application_id
        ├── subscriptions ──► licenses
        ├── customer_documents (versioned via document_group_uuid)
        ├── customer_notes
        ├── customer_tasks
        ├── customer_communications
        └── customer_analytics_snapshots (daily unique per customer)

industries (parent/child master data) ──► customers.industry_id / sub_industry_id
```

Company owns applications. A customer belongs to one company and is entitled to applications through `customer_applications`. Business and Enterprise are organization customers.

## Tables

### `customers`

| Column | Notes |
|--------|--------|
| uuid | Immutable internal identifier |
| customer_number | Immutable public id, `CUS-########` |
| reference | Optional business reference, unique per company |
| company_id | Owning company. The company owns applications |
| customer_type | individual, or organization via business / enterprise |
| company_name | Organization name for business and enterprise customers |
| legal_name, registration_number | Organization identity |
| industry_id, sub_industry_id, industry_other | Industry master data. `industry` stores the display label |
| primary_contact_* | Organization primary contact |
| legal_basis, processing_purpose, retention_until, anonymized_at | Privacy handling |
| timezone | IANA timezone. Defaults from the owning company |
| status | active / inactive / etc. |
| notes | Free text |
| created_by, updated_by | FK → users nullOnDelete |
| deleted_at | Soft delete |

Indexes: company+email/status/type, country, creators.

### `customer_contacts`

FK `customer_id` CASCADE. Types: primary, technical, support, billing, security, compliance, business, emergency. `responsibilities` stores additional tags. Soft deletes. Only one primary contact enforced in service layer.

### `customer_applications`

Entitlement record. The company owns the application; this row assigns it to the customer. FKs: `customer_id`, `application_id`, optional environment, version, release, integration, SLA policy, owner contact. `assignment_number` is `ASN-########`. `ownership_type` is operational responsibility: customer owned, platform managed, or shared. Soft deletes. Duplicate assignment rejected at service layer. Cross-company application assignment rejected.

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
