# Обёртка над docker compose: подгружает секреты из backend/.env в окружение
# процесса, поэтому забытый флаг --env-file больше не подставляет пустые
# JWT_SECRET/POSTGRES_PASSWORD в контейнеры. Использовать вместо `docker compose`:
#   .\dc.ps1 up -d --build
#   .\dc.ps1 exec php php /var/www/migrate.php
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

Get-Content "backend\.env" | ForEach-Object {
    if ($_ -match '^\s*#' -or $_ -match '^\s*$') { return }
    $key, $value = $_ -split '=', 2
    [System.Environment]::SetEnvironmentVariable($key.Trim(), $value.Trim(), 'Process')
}

docker compose @args
