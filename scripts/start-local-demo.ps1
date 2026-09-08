# Start local MySQL (port 3307) + PHP dev server (port 8080) for the College CMS demo.
# Run from project root:  powershell -ExecutionPolicy Bypass -File scripts\start-local-demo.ps1
# Requirements: PHP in PATH, MySQL mysqld (Oracle MySQL 8.4 installed), config.php with matching db port.

# Project root = parent of this script's folder (...\Aaaag\scripts -> ...\Aaaag)
$ErrorActionPreference = "Stop"
$Root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$DataDir = Join-Path $Root "tools\mysql_data"
$Public = Join-Path $Root "public_html"
if (-not (Test-Path $Public)) {
    throw "public_html not found at: $Public (run this script from the Aaaag repo; path to scripts/ must be ...\Aaaag\scripts\start-local-demo.ps1)."
}
$MySQLD = "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe"
$Port = 3307
$WebPort = 8080

if (-not (Test-Path $DataDir)) {
    Write-Host "Initializing MySQL data (first run)..."
    New-Item -ItemType Directory -Path $DataDir -Force | Out-Null
    & $MySQLD --initialize-insecure --datadir=$DataDir
}

$existing = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if (-not $existing) {
    Write-Host "Starting MySQL on port $Port..."
    Start-Process -FilePath $MySQLD -ArgumentList "--datadir=$DataDir","--port=$Port" -WindowStyle Minimized
    Start-Sleep -Seconds 3
} else {
    Write-Host "MySQL already listening on port $Port."
}

$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
Set-Location $Public
$listen = "127.0.0.1:$WebPort"
Write-Host ""
Write-Host "LOCAL ONLY - bound to 127.0.0.1 (not exposed on the network / no tunnel)." -ForegroundColor Green
Write-Host "Open http://$listen  (login: admin@college.edu / password)"
Write-Host "Press Ctrl+C to stop the web server (MySQL keeps running)."
# Bind loopback only so the site is not reachable from other machines.
& php -S $listen router.php
