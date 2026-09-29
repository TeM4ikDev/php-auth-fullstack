# php-auth-fullstack

React (Vite) + PHP за nginx, PostgreSQL, JWT-авторизация (access/refresh, сессии и blacklist в
Redis). Два микросервиса: **auth-service** (`backend/`) и **notification-service** (consumer
RabbitMQ с retry/DLQ, история в MongoDB, письма через Mailhog), плюс `client/` и `admin-panel/`
(Refine) — без отдельного Gateway.

## Запуск через Docker

```bash
docker compose up -d --build
docker compose exec php php /var/www/migrate.php
docker compose exec php php /var/www/seed.php   # первый администратор из ADMIN_EMAIL/ADMIN_PASSWORD
```

Сайт: `http://localhost:8081`. Админка: `http://localhost:8082`.
Notification-service API: `http://localhost:8083`.
Adminer (Postgres): `http://localhost:1500`. Mailhog: `http://localhost:8025`.
RabbitMQ management UI: `http://localhost:15672` (guest/guest). MongoDB: `localhost:27017`.
Redis: `localhost:6379`.

## Тесты

Юнит-тесты не требуют инфраструктуры, интеграционные ходят в Postgres/Mongo/RabbitMQ/Mailhog
из compose — образы `php-auth-fullstack-php`/`php-auth-fullstack-notification` появляются после
`docker compose build` (нужны ради `pdo_pgsql`/`ext-mongodb`, которых нет в хостовом PHP). Без
поднятой инфраструктуры интеграционные тесты **скипаются** (`markTestSkipped`), а не падают.

**auth-service:**

```bash
cd backend
docker run --rm -v "$PWD:/app" -w /app php-auth-fullstack-php php vendor/bin/phpunit --testsuite Unit

docker run --rm -v "$PWD:/app" -w /app --network php-auth-fullstack_network2 \
  -e TEST_DB_HOST=postgres -e TEST_DB_PORT=5432 -e TEST_DB_PASSWORD="$POSTGRES_PASSWORD" \
  php-auth-fullstack-php php vendor/bin/phpunit
```

**notification-service:**

```bash
cd notification-service
docker run --rm -v "$PWD:/app" -w /app --network php-auth-fullstack_network2 \
  -e TEST_MONGO_URI=mongodb://mongo:27017 \
  -e TEST_RABBITMQ_HOST=rabbitmq -e TEST_RABBITMQ_USER=guest -e TEST_RABBITMQ_PASSWORD=guest \
  -e TEST_MAILHOG_HOST=mailhog -e TEST_MAILHOG_URL=http://mailhog:8025 \
  php-auth-fullstack-notification php vendor/bin/phpunit
```

## Запуск без Docker (бэкенд)

Требует локально поднятый Redis (например, `docker compose up -d redis`):

```bash
cd backend
composer install
composer serve      # http://localhost:8000
composer migrate
```

Корневой `.env`/`.env.example` использует только `docker compose` для подстановки `${...}` в
`compose.yaml`. Каждый сервис при запуске вне Docker читает свой собственный `.env`
(`backend/.env`, `notification-service/.env`) — их нужно обновлять параллельно с корневым при
добавлении новых переменных. `JWT_SECRET` в обоих файлах сервисов обязан совпадать.

## Запуск без Docker (notification-service)

Требует локально поднятые Mongo/RabbitMQ/Mailhog/Redis (например,
`docker compose up -d mongo rabbitmq mailhog redis`):

```bash
cd notification-service
composer install
composer serve      # http://localhost:8001 — REST API истории уведомлений
composer consume    # отдельный процесс: consumer событий из RabbitMQ
```

## Фронтенд отдельно (dev-режим)

```bash
cd client
npm install
npm run dev
```

## Админка отдельно (dev-режим)

Требует поднятый compose (см. выше) — Vite проксирует `/api` на `http://localhost:8081`
(auth-service), а `/api/notifications` отдельно на `http://localhost:8083` (notification-service).

```bash
cd admin-panel
npm install
npm run dev   # http://localhost:5174
```

## На VPS

Порт из `compose.yaml` (по умолчанию 8081) должен быть открыт в фаерволе:

```bash
sudo ufw allow 8081/tcp
```

Если хостинг облачный — порт также открывается в security group/панели провайдера отдельно от `ufw`.
