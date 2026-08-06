# Deployment

## Local

- `docker compose up -d` for MySQL/Redis/Mailpit
- `php artisan serve` for API
- `npm run dev` for SPA

## Production (future)

- Separate API and SPA hosting
- Redis queue workers
- Scheduler via `php artisan schedule:work` or cron
- Secure CORS origins and Sanctum stateful domains
