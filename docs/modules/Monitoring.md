# Monitoring & Health Check

Phases **2.7** (Integration Hub foundation) and **9.4** (Enterprise Application Monitoring).

## Overview

Enterprise monitoring for AMS across application health, APIs, webhooks, queues/jobs, integrations, and server/runtime probes.

Aggregates connection logs, platform API logs, webhook delivery, queue depth, job tracks, and infrastructure probes into scores, service status, health checks, monitoring logs, and an incident timeline.

## Monitors

- Application Health
- API Response Time / API Errors (integration + platform)
- Webhook Delivery
- Queue Status / Job Status
- Integration Status
- Server Status (runtime + derived availability)
- Database / Cache / Scheduler probes

## Database

| Table | Purpose |
|-------|---------|
| `monitoring_snapshots` | Periodic scored snapshots |
| `monitoring_alerts` / `monitoring_alert_events` | Threshold alerts |
| `monitoring_logs` | Append-only operational / incident stream |
| `health_checks` | Discrete probe results per capture |
| `service_status` | Latest status per service key |

## Shared Engine

```
Shared/Services/Monitoring/
  HealthMonitor.php          # snapshot + probes + alerts
  MetricsAggregator.php
  ScoreCalculator.php
  AlertEvaluator.php

Domains/Monitoring/Services/
  EnterpriseHealthProbeService.php
  MonitoringService.php
```

## Schedule

```bash
php artisan monitoring:capture   # every 5 minutes + scheduler HealthCheck handler
```

Capture now also writes `health_checks`, upserts `service_status`, and appends non-healthy `monitoring_logs` (+ incident logs for alert events).

## Permissions

- `monitoring.view`
- `monitoring.manage`

## API (`/api/v1/monitoring`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard` | Health scores + charts |
| GET | `/api` | API monitor |
| GET | `/webhooks` | Webhook monitor |
| GET | `/queue` | Queue + job status |
| GET | `/realtime` | Real-time status board |
| GET | `/integrations` | Integration status |
| GET | `/timeline` | Incident timeline |
| GET | `/health-checks` | Health check history |
| GET | `/services` | Service status catalog |
| GET | `/logs` | Monitoring logs |
| GET | `/response-history` | Hourly API history |
| POST | `/capture` | Snapshot + probes + alerts |
| CRUD | `/alerts` | Alert configuration |
| GET | `/alert-events` | Triggered alerts |
| POST | `/alert-events/{uuid}/acknowledge` | Acknowledge |

## Frontend

Sidebar **Monitoring**:

- Health Dashboard
- Real-Time Status (auto-refresh 30s)
- API Monitor
- Webhook Monitor
- Queue Monitor
- Integration Status
- Incident Timeline
- Response History
- Alerts

## Testing

```bash
php artisan test tests/Feature/Monitoring
```
