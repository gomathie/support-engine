<#
    Starts the local development environment.

      .\dev.ps1            Start PostgreSQL, the Laravel server and Vite
      .\dev.ps1 -NoVite    Backend only
      .\dev.ps1 -Fresh     Drop and re-seed the database first

    PHPRC points PHP at this project's php.ini (see setup-php-ini.ps1) so the
    system PHP install stays untouched.
#>

param(
    [switch]$NoVite,
    [switch]$Fresh
)

$ErrorActionPreference = 'Stop'
$env:PHPRC = $PSScriptRoot

if (-not (Test-Path (Join-Path $PSScriptRoot 'php.ini'))) {
    Write-Warning "php.ini is missing. Running setup-php-ini.ps1 first."
    & (Join-Path $PSScriptRoot 'setup-php-ini.ps1')
}

# --- PostgreSQL -----------------------------------------------------------
$dbRunning = docker ps --filter 'name=pilot-lms-db' --format '{{.Names}}'
if ($dbRunning -ne 'pilot-lms-db') {
    $dbExists = docker ps -a --filter 'name=pilot-lms-db' --format '{{.Names}}'
    if ($dbExists -eq 'pilot-lms-db') {
        Write-Host "Starting existing PostgreSQL container..."
        docker start pilot-lms-db | Out-Null
    } else {
        Write-Host "Creating PostgreSQL container..."
        docker run -d --name pilot-lms-db `
            -e POSTGRES_DB=pilot_lms `
            -e POSTGRES_USER=pilot `
            -e POSTGRES_PASSWORD=pilot_secret `
            -p 5432:5432 `
            -v pilot-lms-pgdata:/var/lib/postgresql/data `
            --restart unless-stopped `
            postgres:17-alpine | Out-Null
    }
}

# Wait for the server to accept connections before touching it.
Write-Host "Waiting for PostgreSQL..." -NoNewline
for ($i = 0; $i -lt 30; $i++) {
    docker exec pilot-lms-db pg_isready -U pilot -d pilot_lms *> $null
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Milliseconds 500
    Write-Host "." -NoNewline
}
if ($LASTEXITCODE -ne 0) { throw "PostgreSQL did not become ready." }
Write-Host " ready."

# --- Migrations -----------------------------------------------------------
Push-Location $PSScriptRoot
try {
    if ($Fresh) {
        php artisan migrate:fresh --seed
    } else {
        php artisan migrate --graceful
    }

    # --- Servers ----------------------------------------------------------
    $jobs = @()

    Write-Host "Laravel  -> http://localhost:8000"
    Write-Host "Admin    -> http://localhost:8000/admin"
    $jobs += Start-Process -PassThru -NoNewWindow php -ArgumentList 'artisan', 'serve', '--port=8000'

    $jobs += Start-Process -PassThru -NoNewWindow php -ArgumentList 'artisan', 'queue:listen', '--tries=3'

    if (-not $NoVite) {
        Write-Host "Vite     -> starting"
        $jobs += Start-Process -PassThru -NoNewWindow npm -ArgumentList 'run', 'dev'
    }

    Write-Host ""
    Write-Host "Press Ctrl+C to stop." -ForegroundColor Green
    try {
        Wait-Process -Id ($jobs | ForEach-Object { $_.Id })
    } finally {
        $jobs | ForEach-Object { if (-not $_.HasExited) { Stop-Process -Id $_.Id -Force } }

        <#
            Vite writes public/hot while it runs and deletes it on a clean exit.
            Killed abruptly — Ctrl+C, a closed terminal, a crash — the file is
            left behind, and @vite then points every page at
            http://[::1]:5173 instead of the built assets. Nothing is listening
            there, so the app serves a blank page with no CSS or JS and no error
            to explain it.
        #>
        $hot = Join-Path $PSScriptRoot 'public/hot'
        if (Test-Path $hot) {
            Remove-Item $hot -Force
            Write-Host "Cleaned up public/hot." -ForegroundColor DarkGray
        }
    }
} finally {
    Pop-Location
}
