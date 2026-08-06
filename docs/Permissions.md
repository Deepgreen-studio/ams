# Permissions

RBAC is powered by Spatie Laravel Permission with AMS custom Role/Permission models.

## Naming Convention

```text
{module}.{action}
```

Examples: `users.view`, `companies.manage`, `settings.update`, `audit.export`

## Catalog Source

`backend/app/Domains/Roles/Enums/PermissionModule.php`

Permissions are seeded by `RolesAndPermissionsSeeder`.

## Default Roles (system)

super-admin · company-admin · manager · developer · qa-tester · support-manager · content-editor · compliance-officer · customer · read-only-user · admin (legacy alias)

## Enforcement

1. Route middleware: `permission:...`
2. Policies registered in `AppServiceProvider`
3. Frontend should gate UX using permissions returned with the authenticated user

## Module Docs

- Roles domain: `docs/modules/Roles.md`
- Seeder: `backend/database/seeders/RolesAndPermissionsSeeder.php`
