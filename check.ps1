# check.ps1 - Milestone 4 check script
# Detects changed files and runs relevant lint/test/build

param(
    [switch]$All,
    [switch]$BackendOnly,
    [switch]$FrontendOnly
)

$ErrorActionPreference = "Continue"
$repoRoot = $PSScriptRoot
Set-Location $repoRoot

function Section($msg) {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host " $msg" -ForegroundColor Cyan
    Write-Host "==========================================" -ForegroundColor Cyan
}

$runBackend = $false
$runFrontend = $false

if ($All) {
    $runBackend = $true
    $runFrontend = $true
} elseif ($BackendOnly) {
    $runBackend = $true
} elseif ($FrontendOnly) {
    $runFrontend = $true
} else {
    $changed = @()
    $changed += git diff --name-only HEAD 2>$null
    $changed += git diff --name-only origin/main...HEAD 2>$null
    $changed = $changed | Where-Object { $_ } | Sort-Object -Unique

    Write-Host "Changed files:" -ForegroundColor Yellow
    $changed | ForEach-Object { Write-Host "  - $_" -ForegroundColor Gray }

    if ($changed | Where-Object { $_ -like "src/backend/*" }) { $runBackend = $true }
    if ($changed | Where-Object { $_ -like "src/frontend/*" }) { $runFrontend = $true }
    if ($changed | Where-Object { $_ -match "^infra/|^docker-compose|^check\.ps1" }) {
        $runBackend = $true
        $runFrontend = $true
    }
}

Write-Host ""
Write-Host "Backend checks: $runBackend" -ForegroundColor Yellow
Write-Host "Frontend checks: $runFrontend" -ForegroundColor Yellow

$global:exitCode = 0

# ==========================================
# BACKEND
# ==========================================
if ($runBackend) {
    Section "BACKEND - Laravel"

    Write-Host "`n[1/2] PHPUnit tests..." -ForegroundColor Yellow
    docker compose --profile core exec -T backend php artisan test
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  Backend tests: FAIL" -ForegroundColor Red
        $global:exitCode = 1
    } else {
        Write-Host "  Backend tests: OK" -ForegroundColor Green
    }

    Write-Host "`n[2/2] composer validate..." -ForegroundColor Yellow
    docker compose --profile core exec -T backend composer validate --no-check-publish
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  composer validate: FAIL" -ForegroundColor Red
        $global:exitCode = 1
    } else {
        Write-Host "  composer validate: OK" -ForegroundColor Green
    }
}

# ==========================================
# FRONTEND
# ==========================================
if ($runFrontend) {
    Section "FRONTEND - Nuxt"

    Write-Host "`n[1/1] Frontend build..." -ForegroundColor Yellow
    docker compose --profile core exec -T frontend npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  Frontend build: FAIL" -ForegroundColor Red
        $global:exitCode = 1
    } else {
        Write-Host "  Frontend build: OK" -ForegroundColor Green
    }
}

Write-Host ""
if ($global:exitCode -eq 0) {
    Write-Host "ALL CHECKS PASSED" -ForegroundColor Green
} else {
    Write-Host "SOME CHECKS FAILED (exit code $global:exitCode)" -ForegroundColor Red
}
exit $global:exitCode