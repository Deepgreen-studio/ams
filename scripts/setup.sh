#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "==> Starting infrastructure"
docker compose -f "$ROOT_DIR/docker-compose.yml" up -d

echo "==> Installing backend dependencies"
cd "$ROOT_DIR/backend"
composer install
cp -n .env.example .env || true
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

echo "==> Installing frontend dependencies"
cd "$ROOT_DIR/frontend"
npm install

echo "==> Setup complete"
echo "Backend: cd backend && php artisan serve"
echo "Frontend: cd frontend && npm run dev"
