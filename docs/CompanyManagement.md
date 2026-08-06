# Company Management

See detailed module documentation:

**`docs/modules/Companies.md`**

## Summary

- Multi-company architecture ready (`companies`, `company_user`)
- Departments, teams, office locations
- Branding (logo/favicon/colors) + locale settings
- Soft delete + restore
- Permissions: `companies.view|create|update|delete|restore|manage`

## Paths

- Backend: `backend/app/Domains/Companies/`
- Frontend: `frontend/src/modules/companies/`
- Tests: `backend/tests/Feature/Companies/CompanyManagementTest.php`
