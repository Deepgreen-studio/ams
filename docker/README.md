# Docker

Local infrastructure for AMS lives in the **repository root** `docker-compose.yml`.

## Services

| Service | Ports | Purpose |
|---------|-------|---------|
| MySQL 8.4 | `3306` | Primary database |
| Redis 7 | `6379` | Cache, queue, session |
| Mailpit | `8025` (UI), `1025` (SMTP) | Local mail capture |

## Commands

```bash
# From repository root
docker compose up -d
docker compose ps
docker compose down
```

Application containers (PHP-FPM / Nginx / Vite) can be added in a later deployment phase.
Until then, run Laravel and Vue on the host (Herd/Laragon/local PHP + Node).

See `docs/DeploymentGuide.md` for production checklist.
