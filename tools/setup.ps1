param([string]$PhpBinary)
$ErrorActionPreference = 'Stop'
. "$PSScriptRoot/php-runtime.ps1"
$php = Get-SitePhp $PhpBinary
$phpArgs = Get-SitePhpArgs $php
Write-Host 'Установка копии сайта. Нужна отдельная пустая база. Рабочую базу выбирать нельзя.'
$dbHost = Read-Host 'Адрес базы с портом (например localhost:3306)'
$dbName = Read-Host 'Название новой базы (например web_avtobus)'
$dbUser = Read-Host 'Пользователь базы'
$securePassword = Read-Host 'Пароль базы (Enter, если пароль пустой)' -AsSecureString
$url = Read-Host 'Адрес копии сайта (для проверки: http://127.0.0.1:8083)'
$adminLogin = Read-Host 'Логин администратора (Enter: admin)'
if ([string]::IsNullOrWhiteSpace($adminLogin)) { $adminLogin = 'admin' }
$adminEmail = Read-Host 'Email администратора'
$adminPassword = Read-Host 'Новый пароль администратора, не короче 12 символов' -AsSecureString
$adminPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($adminPassword)
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $env:WEB_AVTOBUS_DB_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    $env:WEB_AVTOBUS_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($adminPointer)
    & $php @phpArgs "$PSScriptRoot/install.php" "--db-host=$dbHost" "--db-name=$dbName" "--db-user=$dbUser" "--url=$url" "--admin-user=$adminLogin" "--admin-email=$adminEmail" --create-database
    if ($LASTEXITCODE -ne 0) { throw 'Установка не завершена. Прочитайте сообщение выше.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($adminPointer)
    Remove-Item Env:WEB_AVTOBUS_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:WEB_AVTOBUS_DB_PASSWORD -ErrorAction SilentlyContinue
}
