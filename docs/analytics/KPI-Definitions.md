# KPI Definitions — Analytics

## Business / Portfolio

| KPI | Formula (current) | Caveat |
|-----|-------------------|--------|
| MRR | `SUM(subscriptions.amount)` where status ∈ {active, trialing} | **Not** interval-normalized; yearly plans inflate MRR |
| Revenue (period) | Sum of subscription amounts for subscriptions **created** in range | Not invoice/recognized revenue |
| Customers total / active / new | Counts from `customers` | Active uses customer status enum |
| Subscriptions active | Count Active+Trialing | — |
| Application sessions / active users | Sum from `application_analytics_daily` (`metric_date`) | — |
| Support tickets open / new | `support_tickets` | — |
| Avg health score | Rollup of latest `customer_analytics_snapshots` | — |
| At-risk customers | Medium/High/Critical risk snapshots | — |

## Security

| KPI | Formula | Caveat |
|-----|---------|--------|
| Logins success / failed | `user_login_histories.status` | Company filter largely ignored |
| Permission / role changes | Spatie `activity_log` where `log_name=roles` | Heuristic event matching |
| GDPR / exports / deletions | `privacy_requests` types + audit deletes | — |
| API key uses | CMS keys + Sanctum tokens `last_used_at` in range | Global |
| Risk score | Weighted from failed logins, ACL, API errors, deletions | Heuristic 0–100 |

## Executive

| KPI | Formula | Caveat |
|-----|---------|--------|
| Business score | Weighted: revenue 20%, growth 15%, customer health 15%, system health 15%, SLA 15%, compliance 10%, security 10% | Thresholds hardcoded (MRR 1k/5k/20k) |
| SLA score | On-track+met / tracked − breach penalty | Returns 75 if no tracked tickets |
| Compliance / security contribution | `100 - risk_score` | Invert of risk |

## Forecasting

Linear projection from business snapshot history (`BusinessAnalyticsService::buildForecast`). Horizon default 14 days (max 90 via request).

## Trends

Monthly / quarterly / yearly buckets from `executive_analytics_snapshots` (fallback: business revenue daily series when executive history empty).
