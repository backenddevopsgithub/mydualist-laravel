param(
    [Parameter(Mandatory = $true, Position = 0)]
    [ValidateSet('smoke', 'public-list', 'public-submit', 'owner-dashboard', 'arafah')]
    [string] $Scenario,

    [string] $BaseUrl = $env:BASE_URL
)

$ErrorActionPreference = 'Stop'

$scriptMap = @{
    'smoke' = 'load-tests/smoke.js'
    'public-list' = 'load-tests/public-list.js'
    'public-submit' = 'load-tests/public-submit.js'
    'owner-dashboard' = 'load-tests/owner-dashboard.js'
    'arafah' = 'load-tests/arafah-mixed.js'
}

if (-not (Get-Command k6 -ErrorAction SilentlyContinue)) {
    Write-Error 'k6 is not installed. Run: winget install k6 --source winget'
}

$manifestPath = Join-Path $PSScriptRoot 'fixtures/manifest.json'
if (-not (Test-Path $manifestPath)) {
    Write-Error 'Missing load-tests/fixtures/manifest.json. Run: php artisan load-test:seed'
}

$args = @('run', $scriptMap[$Scenario])

if ($BaseUrl) {
    $args += @('-e', "BASE_URL=$BaseUrl")
}

Write-Host "Running k6 scenario '$Scenario'..."
& k6 @args
