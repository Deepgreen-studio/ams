# PowerShell setup for Windows / Herd

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot

Write-Host "==> Starting infrastructure (Docker)"
Set-Location $Root
docker compose up -d

Write-Host "==> Backend setup"
Set-Location "$Root\backend"
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

Write-Host "==> Frontend setup"
Set-Location "$Root\frontend"
npm install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }

Write-Host "==> Done"
Write-Host "Backend:  cd backend; php artisan serve"
Write-Host "Frontend: cd frontend; npm run dev"
Write-Host "Admin: admin@ams.test / Password@123"
