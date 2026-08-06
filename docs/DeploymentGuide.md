# Deployment Guide

## Production Checklist

### Application

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong unique `APP_KEY`
- [ ] Correct `APP_URL` and `FRONTEND_URL`
- [ ] Explicit `CORS_ALLOWED_ORIGINS` (never `*` with credentials)
- [ ] `SANCTUM_STATEFUL_DOMAINS` matches SPA host(s)
- [ ] HTTPS only (cookies Secure/SameSite appropriately)

### Database & Cache

- [ ] Managed MySQL 8+
- [ ] Redis for cache/session/queue
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed only what is required (roles permissions on first deploy)
- [ ] Schedule backups

### Storage

- [ ] `php artisan storage:link`
- [ ] Configure S3 (or equivalent) for `FILESYSTEM_*_DISK` when ready
- [ ] Restrict public disk policies

### Workers

- [ ] Queue worker(s): `php artisan queue:work --sleep=1 --tries=3`
- [ ] Scheduler via cron: `* * * * * php artisan schedule:run`
- [ ] Process managers (Supervisor/systemd) with restart on failure

### Frontend

- [ ] `npm run build`
- [ ] Set `VITE_API_BASE_URL` to production API base (`…/api/v1`)
- [ ] Host SPA behind CDN/reverse proxy

### Observability

- [ ] Log channel configured (stack + daily or external)
- [ ] Confirm Audit `error_logs` / `api_logs` retention policy
- [ ] Health: `GET /api/v1/health` and `/up`

### Security

- [ ] Rotate default seeded passwords
- [ ] Restrict admin roles
- [ ] Rate limits enabled (already configured in code)
- [ ] Review file upload limits (`storage.max_upload_kb`)

## Local Infra via Docker

```bash
docker compose up -d
```

See `docker/README.md`.

## Related

- Installation: `docs/Installation.md`
- Deployment notes (legacy filename): `docs/Deployment.md`
