# Audit & Monitoring Module

## Overview

Enterprise Audit Trail, Activity Log, and System Monitoring for AMS Phase 1.7.

## Responsibilities

- Activity browsing/export via Spatie `activity_log`
- Structured `audit_logs` before/after trails
- Login history persistence on auth events
- API request logging middleware
- System event store
- Exception persistence into `error_logs`
- CSV export for activity logs (Excel/PDF architecture-ready)

## Integrations for Future Modules

Call these services from any domain:

- `ActivityLogService::record()`
- `AuditTrailService::record()`
- `SystemEventService::record()`
- `ErrorLogService::capture()`
- `LoginHistoryService` (wired to login/logout)

## Permissions

- `audit.view`
- `audit.export`
- `audit.manage`

## Testing

```bash
php artisan test --filter=AuditMonitoringTest
```
