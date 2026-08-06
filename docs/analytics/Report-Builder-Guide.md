# Report Builder Guide — Analytics

## Capabilities (Phase 9.3)

- Tabular, chart, grouped, and custom report definitions
- Designer config (columns, filters, sorting, grouping)
- Preview before run
- Export: **CSV**, **Excel**, **PDF**, Print-ready HTML, JSON
- Schedule via `scheduled_jobs` (`analytics_report` handler)
- Saved reports + run history + download

## UI routes

| Page | Path |
|------|------|
| Reports list | `/analytics/reports` |
| Designer | `/analytics/reports/:uuid/designer` |
| Saved reports | `/analytics/saved-reports` |

## API workflow

1. `POST /api/v1/analytics/reports` — create definition  
2. `PUT /api/v1/analytics/reports/{uuid}/designer` — save designer config  
3. `POST /api/v1/analytics/reports/{uuid}/preview` — preview payload  
4. `POST /api/v1/analytics/reports/{uuid}/run` — generate artifact (`analytics.export`)  
5. `GET /api/v1/analytics/reports/{uuid}/runs/{run}/download` — download  
6. `PUT /api/v1/analytics/reports/{uuid}/schedule` — attach schedule  

## Permissions

| Action | Permission |
|--------|------------|
| View / preview | `analytics.view` |
| Create / update designer / schedule | `analytics.create` / `analytics.update` |
| Run / download | `analytics.export` |
| Delete | `analytics.delete` |

## Packages

- `phpoffice/phpspreadsheet` — Excel  
- `barryvdh/laravel-dompdf` — PDF  

## Operational notes

- Large exports should run via queue (`GenerateAnalyticsReportJob`).
- Retain `analytics_report_runs` artifacts per retention policy (not yet automated).
- PDF/Excel generation is production-capable; validate font/memory limits on large datasets.
