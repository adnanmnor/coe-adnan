# setup.ps1 - One-command setup untuk fresh clone

Write-Host "=== COE E-Commerce Setup ===" -ForegroundColor Cyan

# 1. Buat .env
if (-not (Test-Path "src\backend\.env")) {
    Write-Host "Creating .env from .env.example..." -ForegroundColor Yellow
    Copy-Item src\backend\.env.example src\backend\.env
}

# 2. Generate cert (kalau tak ada)
if (-not (Test-Path "infra\nginx\certs\server.crt")) {
    Write-Host "Generating self-signed cert..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Force -Path "infra\nginx\certs" | Out-Null
    docker run --rm -v "${PWD}\infra\nginx\certs:/certs" alpine/openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /certs/server.key -out /certs/server.crt -subj "/CN=localhost"
}

# 3. Up containers
Write-Host "Starting containers..." -ForegroundColor Yellow
docker compose --profile core up -d

# 4. Wait
Start-Sleep -Seconds 5

# 5. Generate key
Write-Host "Generating app key..." -ForegroundColor Yellow
docker compose --profile core exec backend php artisan key:generate --force

# 6. Migrate & seed
Write-Host "Running migrations & seeders..." -ForegroundColor Yellow
docker compose --profile core exec backend php artisan migrate:fresh --seed --force

Write-Host ""
Write-Host "=== SETUP COMPLETE ===" -ForegroundColor Green
Write-Host "Frontend:  http://localhost:3000" -ForegroundColor Cyan
Write-Host "Backend:   http://localhost:8000/api" -ForegroundColor Cyan
Write-Host "HTTPS:     https://localhost:8443" -ForegroundColor Cyan
Write-Host "API Docs:  http://localhost:8000/docs/api" -ForegroundColor Cyan
Write-Host "Grafana:   http://localhost:3200 (admin/admin)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Admin:     admin@example.com / password123" -ForegroundColor Yellow