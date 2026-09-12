#!/usr/bin/env bash
# Обёртка над docker compose: подгружает секреты из backend/.env в окружение
# процесса, поэтому забытый флаг --env-file больше не подставляет пустые
# JWT_SECRET/POSTGRES_PASSWORD в контейнеры. Использовать вместо `docker compose`:
#   ./dc.sh up -d --build
#   ./dc.sh exec php php /var/www/migrate.php
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

set -a
source backend/.env
set +a

exec docker compose "$@"
