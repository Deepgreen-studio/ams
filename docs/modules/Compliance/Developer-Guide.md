# Developer Guide — Compliance

## Domain location

```
backend/app/Domains/Compliance/
  Controllers/ Services/ Repositories/ Models/
  Policies/ Requests/ Resources/ Enums/
  Events/ Listeners/ Notifications/ Routes/
frontend/src/modules/compliance/
  pages/ components/ stores/ services/
```

## Design rules

1. Controllers stay thin: validate → authorize → service → `ApiResponse`.  
2. Business rules live in Services; persistence in Repositories.  
3. Use enums for statuses/types; Form Requests for input; API Resources for output.  
4. Prefer UUID identifiers in routes (`{policy}`, `{breach}`, etc.).  
5. Emit domain events; register listeners in `AppServiceProvider::configureComplianceEvents()`.  
6. Gate policies registered in `configurePolicies()`.

## Adding a workflow action

1. Add enum transition if status-based.  
2. Service method inside `DB::transaction`.  
3. Form Request + controller method.  
4. Route with `permission:compliance.*`.  
5. Event + activity listener.  
6. Feature test under `tests/Feature/Compliance/`.  
7. Store method + UI button.

## Policy documents (immutability)

- Model: `PolicyDocument` / table `policies`.  
- Updates must call version recording (`policy_versions`) — never overwrite historical rows.  
- Restore = new highest version with `is_restore` + `restored_from_version`.

## Analytics

- `ComplianceAnalyticsRepository` aggregates live data (no snapshot table in Phase 7.7).  
- Prefer SQL aggregates for large datasets; avoid unbounded `get()` for averages as volume grows.  
- Export: CSV streamed; Excel = UTF-8 BOM CSV as `.xls`; PDF returns 422 `pdf_ready`.

## Frontend conventions

- One Pinia store + Axios service per submodule.  
- Reuse `ComplianceSubnav`, status badges, `PageHeader`, `EmptyState`.  
- Charts: `SimpleLineChart` (applications) + `SimpleBarChart` (compliance).  
- Auth UI does not yet hide actions by permission — API 403 is the backstop.

## Testing

```bash
cd backend
php artisan test --filter=Compliance
```

Feature coverage exists for all seven submodules. Unit tests under `tests/Unit/Compliance` are not yet present — add for scoring, 72h deadline math, and status matrices.

## Security expectations for new code

- Always authorize with Gate + permission middleware.  
- Do not return unnecessary PII in list resources (avoid bloating `export_payload` on index).  
- Plan company-scoped policy checks before multi-tenant SaaS.  
- Soft-delete parents carefully: cascading FKs can remove audit children on hard delete.

## Key files

| Concern | Path |
|---------|------|
| Routes | `Domains/Compliance/Routes/api.php` |
| Permissions | `Enums/CompliancePermission.php` |
| Gates | `app/Providers/AppServiceProvider.php` |
| Vue routes | `frontend/src/router/index.js` (`compliance.*`) |
