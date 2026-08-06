# AMS Backend (Laravel 12)

API-first Laravel application.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Ensure MySQL and Redis are running (`docker compose up -d` from repo root).

Auth tests:

```bash
php artisan test --filter=AuthenticationTest
```
