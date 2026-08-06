# Application Management — Database Documentation

## Migrations

| Migration | Tables |
|-----------|--------|
| `2026_08_03_190000_create_applications_table` | `applications` |
| `2026_08_03_191000_create_application_versions_table` | `application_versions` |
| `2026_08_03_192000_create_application_environments_table` | `application_environments` |
| `2026_08_03_193000_create_application_configurations_tables` | `application_configurations`, `application_configuration_histories` |
| `2026_08_03_194000_create_application_releases_tables` | `application_releases`, `application_release_notes` |
| `2026_08_03_195000_create_application_monitoring_tables` | `application_crash_reports`, `application_health_metrics`, `application_monitoring_alerts`, `application_monitoring_alert_events` |
| `2026_08_03_196000_create_application_analytics_tables` | `application_analytics_daily`, `application_analytics_countries`, `application_analytics_devices`, `application_analytics_heatmaps` |

## Entity Relationship (logical)

```
companies 1──* applications
applications 1──* application_versions
applications 1──* application_environments
applications 1──* application_configurations (*──* histories)
applications 1──* application_releases (*──* notes)
application_releases *──1 application_versions
application_releases *──0..1 application_environments
applications 1──* application_crash_reports
applications 1──* application_health_metrics
applications 1──* application_monitoring_alerts (*──* events)
applications 1──* application_analytics_daily
applications 1──* application_analytics_countries|devices|heatmaps
```

## Core Tables

### `applications`
- UUID, `company_id`, optional `integration_id`
- `name`, `slug` (unique per company), `platform`, `category`, `status`, `visibility`
- `current_version`, `minimum_supported_version`
- Soft deletes, audit users

### `application_versions`
- Semver parts + `version_number` (unique per app)
- `status`, `release_date`, `release_notes`, `build_number`
- Indexes: status, semver composite

### `application_environments`
- Unique `slug` and `type` per application
- `api_url`, `web_url`, `status`, `health_status`, `is_current`
- `variables` **encrypted JSON**

### `application_configurations` / `_histories`
- Scoped by `(application_id, environment_id, type)` unique
- `payload` **encrypted JSON**, monotonic `version`
- Histories store snapshots for restore

### `application_releases` / `_notes`
- Requires `application_version_id`
- Status / approval_status / rollback_status enums
- Schedule + deployment timestamps, approver / rollback actor FKs

### Monitoring tables
- Crashes: type, stack_trace, crash_log, fingerprint, device fields, occurrence_count
- Health metrics: health_score + rates + resource averages
- Alerts + alert events with acknowledge flow

### Analytics tables
- Daily KPI unique `(application_id, metric_date)`
- Country unique `(application_id, metric_date, country_code)`
- Devices & heatmaps indexed by app + date

## Indexing Notes

Short custom index names used where MySQL 64-char limits apply. Date filters should use `whereDate` with cast columns to avoid SQLite/MySQL mismatch.

## Encryption

| Column | Mechanism |
|--------|-----------|
| `application_environments.variables` | `encrypted:array` cast |
| `application_configurations.payload` | `encrypted:array` cast |
| `application_configuration_histories.payload` | `encrypted:array` cast |

API responses mask secrets (`********`) for environment variables and sensitive configuration types.
