# Upload Desk to desk.agricart.pk. Does not push to the AgriCart git remote.
# Leave this unrun until the app is ready:
#   powershell -ExecutionPolicy Bypass -File deploy/hostinger.ps1 -Run

param(
    [switch] $Run
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $root '.env.production'
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'

if (-not (Test-Path $envFile)) {
    throw ".env.production is missing."
}

if (-not $Run) {
    Write-Host "Deploy is ready and was not started."
    Write-Host "Site folder on the server: domains/agricart.pk/public_html/desk"
    Write-Host "App code will go to: ~/apps/desk"
    Write-Host "Database: u262659235_desk on the server only."
    Write-Host "When you want it live: powershell -ExecutionPolicy Bypass -File deploy/hostinger.ps1 -Run"
    exit 0
}

$password = (Get-Content $envFile | Where-Object { $_ -like 'DB_PASSWORD=*' } | Select-Object -First 1).Substring(12)
$ask = Join-Path $env:TEMP 'desk-ask.cmd'
Set-Content -Path $ask -Value "@echo $password" -Encoding ASCII
$env:SSH_ASKPASS = $ask
$env:SSH_ASKPASS_REQUIRE = 'force'
$env:DISPLAY = '1'

$archive = Join-Path $env:TEMP 'desk-release.tar'
if (Test-Path $archive) {
    Remove-Item $archive -Force
}

Push-Location $root
try {
    & tar -cf $archive `
        --exclude=.git `
        --exclude=.env `
        --exclude=.env.production `
        --exclude=node_modules `
        --exclude=vendor `
        --exclude=storage/logs `
        --exclude=storage/framework/cache/data `
        --exclude=storage/framework/sessions `
        --exclude=storage/framework/views `
        --exclude=storage/app/connectors `
        --exclude=storage/app/private `
        --exclude=database/database.sqlite `
        --exclude=bootstrap/cache `
        --exclude=public/hot `
        --exclude=public/storage `
        --exclude=tests `
        .
}
finally {
    Pop-Location
}

scp -o StrictHostKeyChecking=accept-new -P 65002 $archive u262659235@145.79.25.63:apps/desk-release.tar

$remote = @'
set -e
mkdir -p "$HOME/apps/desk" "$HOME/desk-shared"
rm -rf "$HOME/apps/desk-next"
mkdir -p "$HOME/apps/desk-next"
tar -xf "$HOME/apps/desk-release.tar" -C "$HOME/apps/desk-next"
rm -f "$HOME/apps/desk-release.tar"
if [ -f "$HOME/desk-shared/.env" ]; then
  cp "$HOME/desk-shared/.env" "$HOME/apps/desk-next/.env"
fi
cd "$HOME/apps/desk-next"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force --no-interaction
rm -rf "$HOME/apps/desk"
mv "$HOME/apps/desk-next" "$HOME/apps/desk"
cd "$HOME/apps/desk"
php artisan config:cache
php artisan route:cache
php artisan view:cache
web="$HOME/domains/agricart.pk/public_html/desk"
find "$web" -mindepth 1 -maxdepth 1 ! -name .htaccess -exec rm -rf {} +
cp -a "$HOME/apps/desk/public/." "$web/"
php <<'PY'
<?php
$index = getenv('HOME').'/domains/agricart.pk/public_html/desk/index.php';
$src = file_get_contents($index);
$src = str_replace("__DIR__.'/..", "'/home/u262659235/apps/desk", $src);
file_put_contents($index, $src);
PY
rm -f "$HOME/apps/desk-remote.sh"
echo DEPLOYED
'@

$remotePath = Join-Path $env:TEMP 'desk-remote.sh'
[IO.File]::WriteAllText($remotePath, ($remote -replace "`r", ""), [Text.UTF8Encoding]::new($false))
scp -o StrictHostKeyChecking=accept-new -P 65002 $remotePath u262659235@145.79.25.63:apps/desk-remote.sh
ssh -o StrictHostKeyChecking=accept-new -p 65002 u262659235@145.79.25.63 "bash apps/desk-remote.sh"
if ($LASTEXITCODE -ne 0) {
    throw "Deploy failed."
}
Remove-Item $remotePath -Force -ErrorAction SilentlyContinue
Remove-Item $ask -Force -ErrorAction SilentlyContinue
Remove-Item $archive -Force -ErrorAction SilentlyContinue
Write-Host "Desk is on https://desk.agricart.pk"
