# Enterprise Application Management System (AMS)

Monorepo for the Enterprise Application Management System.

Phase 1 foundation is complete: Authentication, Users, Roles & Permissions, Companies, System Settings, Shared Services, and Audit & Monitoring.

This project is a monorepo that contains the backend and frontend for the Enterprise Application Management System.

The backend is a Laravel 12 API (DDD modular monolith) and the frontend is a Vue 3 SPA.

The project is organized into a root directory with the following structure:

## Root Structure

```text
AMS/
├── backend/          # Laravel 12 API (DDD modular monolith)
├── frontend/         # Vue 3 SPA
├── docs/             # Architecture and delivery docs
├── docker/           # Docker notes (compose at repo root)
├── scripts/          # Setup and utility scripts
├── .github/          # CI workflows
├── docker-compose.yml
├── README.md
├── LICENSE
└── .env.example
```

## Technology Stack

| Layer | Stack |
|-------|-------|
| Backend | Laravel 12, PHP 8.3+, MySQL 8, Redis, Sanctum, Spatie Permission, Spatie Activitylog |
| Frontend | Vue 3, Vite, Tailwind CSS, Pinia, Vue Router, Axios, Heroicons |
| Architecture | DDD Modular Monolith, Repository + Service layers, REST API First (`/api/v1`) |

## Quick Start

### 1. Infrastructure

```bash
docker compose up -d
```

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* and Redis in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
# Windows alternative if serve fails: php -S 127.0.0.1:8080 -t public
```

Default admin (local seed only — change immediately in shared environments):

- Email: `admin@ams.test`
- Password: `Password@123`

### 3. Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Open `http://localhost:5173`

## Development Commands

| Area | Command |
|------|---------|
| Migrate | `cd backend && php artisan migrate` |
| Seed | `cd backend && php artisan db:seed` |
| Test | `cd backend && php artisan test` |
| Format (Pint) | `cd backend && vendor/bin/pint` |
| Queue worker | `cd backend && php artisan queue:work` |
| Frontend build | `cd frontend && npm run build` |
| Frontend lint | `cd frontend && npm run lint` (if configured) |

## Phase 1 Modules (Complete)

1. Authentication & Security  
2. User Management  
3. Roles & Permissions  
4. Company & Organization Management  
5. System Settings & Shared Services  
6. Audit Trail, Activity Log & System Monitoring  
7. Foundation Review & Production Readiness (this milestone)

## Phase 2 (Next)

Integration Hub — do not start until Phase 1 approval.

## Documentation

| Doc | Path |
|-----|------|
| Architecture | `docs/Architecture.md` |
| Folder Structure | `docs/FolderStructure.md` |
| Database | `docs/Database.md` |
| API | `docs/API.md` |
| Authentication | `docs/Authentication.md` |
| Permissions | `docs/Permissions.md` |
| Companies | `docs/CompanyManagement.md` |
| Settings | `docs/Settings.md` |
| Audit | `docs/AuditTrail.md` |
| Development | `docs/DevelopmentGuide.md` |
| Deployment | `docs/DeploymentGuide.md` |
| Contributing | `docs/ContributionGuide.md` |
| Coding Standards | `docs/CodingStandards.md` |
| Phase 1 Review | `docs/Phase-1-Review-Report.md` |

## License

See `LICENSE`.
