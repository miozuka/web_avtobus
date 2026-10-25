# Общий поиск PHP для команд установки и запуска. На других компьютерах путь можно передать явно.
function Get-SitePhp {
    param([string]$PhpBinary)
    if ($PhpBinary) { return (Get-Command $PhpBinary -ErrorAction Stop).Source }
    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    $runtime = Join-Path $env:APPDATA 'Local/lightning-services'
    $candidates = @(Get-ChildItem -Path "$runtime/php-*/bin/win*/php.exe" -ErrorAction SilentlyContinue | Sort-Object FullName -Descending)
    if (!$candidates) { throw 'PHP не найден. Установите PHP 8.2+ или Local, либо укажите -PhpBinary.' }
    return $candidates[0].FullName
}
function Get-SitePhpArgs {
    param([string]$PhpBinary)
    # Поставляемый с Local PHP не всегда видит свой php.ini при запуске вне Site Shell.
    $extensionDir = Join-Path (Split-Path $PhpBinary -Parent) 'ext'
    $args = @('-d', 'memory_limit=512M')
    if ((Test-Path "$extensionDir/php_mysqli.dll") -and $PhpBinary -match 'lightning-services') {
        $args = @('-n', '-d', "extension_dir=$extensionDir") + $args
        foreach ($extension in @('mysqli', 'mbstring', 'openssl', 'curl', 'gd', 'exif', 'fileinfo', 'intl', 'zip', 'sodium')) {
            if (Test-Path "$extensionDir/php_$extension.dll") { $args += @('-d', "extension=$extension") }
        }
    }
    $args += @('-d', 'upload_max_filesize=32M', '-d', 'post_max_size=40M', '-d', 'max_execution_time=120')
    return $args
}
