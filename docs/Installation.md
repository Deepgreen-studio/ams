# Installation

## Prerequisites

- PHP 8.3+
- Composer 2+
- Node.js 20+
- Docker (MySQL, Redis, Mailpit)

## Steps

1. Copy root `.env.example` values as needed for Docker ports.
2. Start infrastructure: `docker compose up -d`
3. Backend:
   - `cd backend`
   - `composer install`
   - `cp .env.example .env`
   - configure DB/Redis
   - `php artisan key:generate`
   - `php artisan migrate --seed`
4. Frontend:
   - `cd frontend`
   - `npm install`
   - `cp .env.example .env`
   - `npm run dev`

Default admin: `admin@ams.test` / `Password@123`
