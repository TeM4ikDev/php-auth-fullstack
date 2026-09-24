# php-auth-fullstack

React (Vite) + PHP за nginx, PostgreSQL, JWT-авторизация. Два микросервиса: **auth-service**
(`backend/`) и **notification-service** — consumer событий из RabbitMQ, история уведомлений
в MongoDB, письма через Mailhog. Оба выдают и проверяют один и тот же JWT (общий `JWT_SECRET`),
без отдельного Gateway.

## Запуск через Docker

```bash
docker compose up -d --build
docker compose exec php php /var/www/migrate.php
docker compose exec php php /var/www/seed.php   # первый администратор из ADMIN_EMAIL/ADMIN_PASSWORD
```

Сайт: `http://localhost:8081` (порт задан в `compose.yaml`, сервис `nginx`).
Админка: `http://localhost:8082` — входит `ADMIN` (видит всё) и `ANALYST` (только история уведомлений).
Notification-service API: `http://localhost:8083` (тот же JWT, что у auth-service).
Adminer (Postgres): `http://localhost:1500`.
Mailhog (письма подтверждения и уведомлений): `http://localhost:8025`.
RabbitMQ management UI: `http://localhost:15672` (guest/guest по умолчанию).
MongoDB (история уведомлений): `localhost:27017`.

## Роли

`CUSTOMER` (по умолчанию при регистрации) → `ANALYST` → `ADMIN`. Роль `ADMIN` не выдаётся через самостоятельную регистрацию — только сидом или назначением из админки. Админ не может забанить или понизить в роли самого себя (проверяется на бэкенде).

## Подтверждение email

Логин закрыт жёстким гейтом, пока email не подтверждён: `POST /api/auth/register` создаёт
пользователя и отправляет письмо со ссылкой `CLIENT_URL/verify-email?token=...` через Mailhog,
`POST /api/auth/login` для неподтверждённого адреса отвечает `403`. Открыть ссылку — значит
вызвать `POST /api/auth/verify-email` с этим токеном (страница `client/` делает это сама).
Сидовый админ создаётся уже подтверждённым.

## Notification Service

При регистрации auth-service публикует `user.registered` в топик-exchange `app_events`
(RabbitMQ). `notification-service` слушает очередь `notification_service_events` (биндинги
`user.registered`, `order.paid`, `order.confirmed`, `order.cancelled` — три последних оставлены
пустыми заглушками, Order Service в этом репозитории не реализован), на `user.registered`
шлёт приветственное письмо через Mailhog и пишет документ в MongoDB (`event`, `channel`,
`recipient`, `payload`, `status`, `attempts`, таймстемпы). Без retry/DLQ: неудача отправки
фиксируется статусом `failed`, сообщение всё равно подтверждается (ack).

`GET /api/notifications` и `GET /api/notifications/{id}` — история уведомлений, доступна ролям
`ADMIN` и `ANALYST` (тот же JWT, что и у auth-service, валидируется без обращения к БД
пользователей — сервис вообще не знает про Postgres).

## Тесты

Юнит-тесты не требуют базы, интеграционные ходят в Postgres из compose:

```bash
cd backend
docker run --rm -v "$PWD:/app" -w /app php-auth-fullstack-php php vendor/bin/phpunit --testsuite Unit

docker run --rm -v "$PWD:/app" -w /app --network php-auth-fullstack_network2 \
  -e TEST_DB_HOST=postgres -e TEST_DB_PORT=5432 -e TEST_DB_PASSWORD="$POSTGRES_PASSWORD" \
  php-auth-fullstack-php php vendor/bin/phpunit
```

Образ `php-auth-fullstack-php` появляется после `docker compose build`; он нужен ради расширения `pdo_pgsql`.

## Запуск без Docker (бэкенд)

```bash
cd backend
composer install
composer serve      # http://localhost:8000
composer migrate
```

Корневой `.env`/`.env.example` использует только `docker compose` для подстановки `${...}` в
`compose.yaml`. Каждый сервис при запуске вне Docker читает свой собственный `.env`
(`backend/.env`, `notification-service/.env`) — их нужно обновлять параллельно с корневым при
добавлении новых переменных. `JWT_SECRET` в обоих файлах сервисов обязан совпадать — иначе
notification-service не сможет проверить токены, выданные auth-service.

## Запуск без Docker (notification-service)

Требует локально поднятые Mongo/RabbitMQ/Mailhog (например, `docker compose up -d mongo rabbitmq mailhog`):

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

Требует поднятый compose (см. выше) — Vite проксирует `/api` на `http://localhost:8081` (auth-service),
а `/api/notifications` отдельно на `http://localhost:8083` (notification-service).

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
