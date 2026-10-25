param([string]$PhpBinary, [int]$Port = 8083, [string]$NginxBinary)
$ErrorActionPreference = 'Stop'
. "$PSScriptRoot/php-runtime.ps1"
$php = Get-SitePhp $PhpBinary
$phpArgs = Get-SitePhpArgs $php
$public = Join-Path (Split-Path $PSScriptRoot -Parent) 'public'
if (!(Test-Path -LiteralPath "$public/wp-config-local.php") -and !(($env:WEB_AVTOBUS_CONFIG) -and (Test-Path -LiteralPath $env:WEB_AVTOBUS_CONFIG))) {
    throw 'Сначала выполните tools/setup.ps1, как описано в README.md.'
}
if ($Port -lt 1024 -or $Port -gt 65535) { throw 'Порт должен быть от 1024 до 65535.' }
Write-Host "Сайт: http://127.0.0.1:$Port/ — панель WordPress: http://127.0.0.1:$Port/wp-admin/"
Write-Host 'Для остановки нажмите Ctrl+C. Это сервер для локальной проверки, не для публикации в интернете.'
if (!$NginxBinary) {
    $runtime = Join-Path $env:APPDATA 'Local/lightning-services'
    $candidate = @(Get-ChildItem -Path "$runtime/nginx-*/bin/win*/nginx.exe" -ErrorAction SilentlyContinue | Sort-Object FullName -Descending)
    if ($candidate) { $NginxBinary = $candidate[0].FullName }
}
$cgi = Join-Path (Split-Path $php -Parent) 'php-cgi.exe'
if ($NginxBinary -and (Test-Path -LiteralPath $cgi)) {
    # Несколько PHP-обработчиков нужны для REST API и запросов WordPress к самому себе.
    & "$PSScriptRoot/start-nginx.ps1" -PhpCgi $cgi -PhpArgs $phpArgs -NginxBinary $NginxBinary -PublicRoot $public -Port $Port
} else {
    Write-Warning 'Nginx не найден. Простой PHP-сервер показывает страницы, но проверка REST/loopback в Site Health может сообщать о тайм-ауте. Для полной проверки используйте Local или Apache/Nginx.'
    & $php @phpArgs -S "127.0.0.1:$Port" -t $public "$PSScriptRoot/router.php"
    if ($LASTEXITCODE -ne 0) { throw 'PHP-сервер остановился с ошибкой.' }
}
