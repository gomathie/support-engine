<#
    Generates php.ini in the project root from your system php.ini, with the
    extensions this project needs enabled (pdo_pgsql, pgsql, gd, intl, sodium).

    Why this exists: the system php.ini lives in a protected directory
    (C:\Program Files\...) and editing it needs an elevated shell. Rather than
    requiring admin rights — or changing PHP globally and risking other projects
    such as XAMPP — this project ships its own php.ini and points PHP at it with
    the PHPRC environment variable. See dev.ps1.

    Run once after cloning:  .\setup-php-ini.ps1
#>

$ErrorActionPreference = 'Stop'

$phpExe = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $phpExe) { throw "php was not found on PATH." }

$phpDir = Split-Path -Parent $phpExe
$extDir = Join-Path $phpDir 'ext'
if (-not (Test-Path $extDir)) { throw "Extension directory not found: $extDir" }

# Prefer the ini PHP already loads; fall back to the distributed templates.
$sourceIni = & $phpExe -r "echo php_ini_loaded_file();"
if (-not $sourceIni -or -not (Test-Path $sourceIni)) {
    foreach ($candidate in @('php.ini-development', 'php.ini-production')) {
        $path = Join-Path $phpDir $candidate
        if (Test-Path $path) { $sourceIni = $path; break }
    }
}
if (-not $sourceIni -or -not (Test-Path $sourceIni)) { throw "No source php.ini found in $phpDir." }

Write-Host "Source php.ini : $sourceIni"
Write-Host "Extension dir  : $extDir"

$required = @('pdo_pgsql', 'pgsql', 'gd', 'intl', 'sodium', 'mbstring', 'openssl', 'curl', 'fileinfo', 'zip')

$ini = Get-Content -Raw -Path $sourceIni

# Uncomment the extensions we need. Handles CRLF line endings.
foreach ($ext in $required) {
    $ini = $ini -replace "(?m)^;(extension=$ext)(\r?)$", '$1$2'
}

# extension_dir must be absolute — PHP resolves a relative one against the CWD,
# and artisan runs from the project root, not the PHP directory.
$ini = $ini -replace '(?m)^;?\s*extension_dir\s*=.*$', ('extension_dir = "' + $extDir + '"')

# Composer and Filament asset publishing are memory-hungry.
$ini = $ini -replace '(?m)^;?memory_limit\s*=.*$', 'memory_limit = 512M'

$target = Join-Path $PSScriptRoot 'php.ini'
Set-Content -Path $target -Value $ini -Encoding utf8 -NoNewline
Write-Host "Wrote          : $target"

# Verify.
$env:PHPRC = $PSScriptRoot
$missing = @()
foreach ($ext in $required) {
    $loaded = & $phpExe -r "echo extension_loaded('$ext') ? '1' : '0';"
    if ($loaded -ne '1') { $missing += $ext }
}

if ($missing.Count -gt 0) {
    Write-Warning ("These extensions still will not load: " + ($missing -join ', '))
    Write-Warning "Check that the matching php_*.dll files exist in $extDir"
    exit 1
}

Write-Host ""
Write-Host "All required extensions load. Run .\dev.ps1 to start the app." -ForegroundColor Green
