param([string]$PhpCgi, [string[]]$PhpArgs, [string]$NginxBinary, [string]$PublicRoot, [int]$Port)
$ErrorActionPreference = 'Stop'
$runtime = Join-Path ([IO.Path]::GetTempPath()) ('web_avtobus-'+[guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $runtime | Out-Null
# Относительные пути и временная ссылка позволяют Nginx работать с кириллицей в имени пользователя.
New-Item -ItemType Directory -Path "$runtime/logs","$runtime/temp" | Out-Null
$publicLink = Join-Path $runtime 'public'
New-Item -ItemType Junction -Path $publicLink -Target $PublicRoot | Out-Null
$workers = @()
$upstream = @()
$nginx = $null
$oldLimit = $env:PHP_FCGI_MAX_REQUESTS
try {
    $env:PHP_FCGI_MAX_REQUESTS = '10000'
    for ($index=0; $index -lt 4; $index++) {
        $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback,0)
        $listener.Start()
        $phpPort = $listener.LocalEndpoint.Port
        $listener.Stop()
        $quotedArgs = foreach ($arg in $PhpArgs) { '"'+$arg.Replace('"','\"')+'"' }
        $worker = Start-Process -FilePath $PhpCgi -ArgumentList ($quotedArgs+@('-b',"127.0.0.1:$phpPort")) -WorkingDirectory $runtime -WindowStyle Hidden -PassThru -RedirectStandardError "$runtime/php-$index.log" -RedirectStandardOutput "$runtime/php-$index.out"
        $workers += $worker
        $upstream += "server 127.0.0.1:$phpPort;"
        $ready = $false
        for ($attempt=0; $attempt -lt 20; $attempt++) {
            $client = [Net.Sockets.TcpClient]::new()
            try { $client.Connect('127.0.0.1',$phpPort); $ready=$true; break } catch { Start-Sleep -Milliseconds 100 } finally { $client.Dispose() }
        }
        if (!$ready) { throw 'Не удалось запустить обработчик PHP. Проверьте PHP и его расширения.' }
    }
    $config = @'
worker_processes 1;
pid nginx.pid;
error_log error.log;
events { worker_connections 256; }
http {
    access_log off;
    default_type application/octet-stream;
    types {
        text/html html htm; text/css css; application/javascript js;
        application/json json; image/jpeg jpg jpeg; image/png png; image/gif gif;
        image/webp webp; image/svg+xml svg; image/x-icon ico;
        font/woff2 woff2; font/woff woff; application/pdf pdf;
    }
    client_max_body_size 40m;
    upstream wordpress_php { __UPSTREAM__ }
    server {
        listen 127.0.0.1:__PORT__;
        server_name 127.0.0.1 localhost;
        root public;
        index index.php;
        location / { try_files $uri $uri/ /index.php?$args; }
        location ~ /\. { deny all; }
        location ~ ^/wp-config(?:-[^/]*)?\.php$ { deny all; }
        location ~ \.php$ {
            try_files $uri =404;
            fastcgi_pass wordpress_php;
            fastcgi_read_timeout 120s;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param QUERY_STRING $query_string;
            fastcgi_param REQUEST_METHOD $request_method;
            fastcgi_param CONTENT_TYPE $content_type;
            fastcgi_param CONTENT_LENGTH $content_length;
            fastcgi_param SCRIPT_NAME $fastcgi_script_name;
            fastcgi_param REQUEST_URI $request_uri;
            fastcgi_param DOCUMENT_URI $document_uri;
            fastcgi_param DOCUMENT_ROOT $document_root;
            fastcgi_param SERVER_PROTOCOL $server_protocol;
            fastcgi_param REMOTE_ADDR $remote_addr;
            fastcgi_param REMOTE_PORT $remote_port;
            fastcgi_param SERVER_ADDR $server_addr;
            fastcgi_param SERVER_PORT $server_port;
            fastcgi_param SERVER_NAME $host;
            fastcgi_param HTTP_HOST $http_host;
            fastcgi_param HTTP_PROXY "";
            fastcgi_param REDIRECT_STATUS 200;
        }
    }
}
'@
    $config = $config.Replace('__UPSTREAM__',($upstream -join ' ')).Replace('__PORT__',[string]$Port)
    [IO.File]::WriteAllText("$runtime/nginx.conf",$config,[Text.UTF8Encoding]::new($false))
    Push-Location -LiteralPath $runtime
    try { & $NginxBinary -p './' -c 'nginx.conf' -t } finally { Pop-Location }
    if ($LASTEXITCODE -ne 0) { throw 'Не прошла проверка конфигурации локального Nginx.' }
    $nginx = Start-Process -FilePath $NginxBinary -ArgumentList @('-p','./','-c','nginx.conf','-g','"daemon off;"') -WorkingDirectory $runtime -WindowStyle Hidden -PassThru
    Write-Host 'Локальный Nginx запущен с четырьмя обработчиками PHP; REST API и внутренние запросы доступны.'
    while (!$nginx.HasExited) { Start-Sleep -Milliseconds 250 }
    if ($nginx.ExitCode -ne 0) { throw 'Nginx остановился с ошибкой. Проверьте доступность выбранного порта.' }
} finally {
    if ($nginx -and !$nginx.HasExited) {
        Push-Location -LiteralPath $runtime
        try { & $NginxBinary -p './' -c 'nginx.conf' -s quit } finally { Pop-Location }
        if (!$nginx.WaitForExit(3000)) { $nginx.Kill() }
    }
    foreach ($worker in $workers) { if (!$worker.HasExited) { $worker.Kill(); $worker.WaitForExit(3000) | Out-Null } }
    $env:PHP_FCGI_MAX_REQUESTS = $oldLimit
    $resolved=(Resolve-Path -LiteralPath $runtime).Path
    $tempRoot=(Resolve-Path -LiteralPath ([IO.Path]::GetTempPath())).Path.TrimEnd('\')
    if (!$resolved.StartsWith($tempRoot+'\') -or (Split-Path $resolved -Leaf) -notlike 'web_avtobus-*') { throw 'Неверная папка временного сервера.' }
    # Сначала удаляем саму ссылку, чтобы очистка временной папки не затронула файлы сайта.
    if (Test-Path -LiteralPath $publicLink) { Remove-Item -LiteralPath $publicLink -Force }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
