# php-auth-fullstack

React (Vite) + PHP за nginx, PostgreSQL, JWT-авторизация. Два микросервиса: **auth-service**
(`backend/`) и **notification-service** — consumer событий из RabbitMQ, история уведомлений
в MongoDB, письма через Mailhog. Оба выдают/проверяют один и тот же JWT (общий `JWT_SECRET`) и
читают общий Redis для blacklist отозванных токенов — без отдельного Gateway.

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
Redis (сессии, blacklist токенов, refresh-токены, rate limit): `localhost:6379`.

## Роли

`CUSTOMER` (по умолчанию при регистрации) → `ANALYST` → `ADMIN`. Роль `ADMIN` не выдаётся через самостоятельную регистрацию — только сидом или назначением из админки. Админ не может забанить или понизить в роли самого себя (проверяется на бэкенде).

## Подтверждение email

Логин закрыт жёстким гейтом, пока email не подтверждён: `POST /api/auth/register` создаёт
пользователя и отправляет письмо со ссылкой `CLIENT_URL/verify-email?token=...` через Mailhog,
`POST /api/auth/login` для неподтверждённого адреса отвечает `403`. Открыть ссылку — значит
вызвать `POST /api/auth/verify-email` с этим токеном (страница `client/` делает это сама).
Сидовый админ создаётся уже подтверждённым.

## JWT: access/refresh токены и Redis

`POST /api/auth/login` отдаёт пару токенов (`accessToken` живёт 15 минут, `refreshToken` — 30
дней) и `expiresIn`. `POST /api/auth/refresh` с телом `{"refreshToken": "..."}` выпускает новую
пару и **ротирует** refresh-токен — предъявленный токен сразу удаляется из Redis, повторное его
использование отвечает `401`. `POST /api/auth/logout` (с `AuthMiddleware`) кладёт `jti`
access-токена в blacklist на оставшийся срок его жизни и завершает сессию.

В Redis хранится: `blacklist:{jti}` (отозванные токены — читают оба сервиса), `session:*` и
`sessions:{userId}` (активные сессии пользователя), `refresh:*` (refresh-токены — в Redis
попадает только их sha256, не сам токен), `ratelimit:*` (счётчики rate limit вместо файлов на
диске). Бан, смена роли, смена пароля и удаление аккаунта отзывают **все** сессии пользователя
разом. `client/` и `admin-panel/` автоматически обновляют access-токен по 401 и повторяют
исходный запрос (конкурентные 401 не порождают несколько параллельных `refresh`).

## Notification Service

При регистрации auth-service публикует `user.registered` в топик-exchange `app_events`
(RabbitMQ). `notification-service` слушает очередь `notification_service_events` (биндинги
`user.registered`, `order.paid`, `order.confirmed`, `order.cancelled` — три последних оставлены
пустыми заглушками, Order Service в этом репозитории не реализован), на `user.registered`
шлёт приветственное письмо через Mailhog и пишет документ в MongoDB (`event`, `channel`,
`recipient`, `payload`, `status`, `attempts`, `lastError`, `deadLettered`, таймстемпы).

**Retry и DLQ.** Неудачная отправка не проваливается сразу: сообщение уходит в одну из трёх
retry-очередей (`notification_retry.1/2/3`, TTL 5с → 25с → 125с — экспоненциальный backoff через
`x-dead-letter-exchange`, без `sleep` в PHP), документ получает статус `retrying`. После третьей
неудачной попытки (4 попытки всего) документ помечается `failed` с `deadLettered: true`, а
сообщение оседает в `notification_service_events.dlq`. Разобрать DLQ можно вручную:
`POST /api/notifications/{id}/replay` (только `ADMIN`) сбрасывает документ в `pending` и
публикует событие заново с исходным routing key.

`GET /api/notifications` и `GET /api/notifications/{id}` — история уведомлений, доступна ролям
`ADMIN` и `ANALYST` (тот же JWT, что и у auth-service, включая проверку blacklist в общем Redis —
сервис не знает про Postgres, но знает про Redis и читает его только на чтение).

## Тесты

Юнит-тесты не требуют инфраструктуры, интеграционные ходят в Postgres/Mongo/RabbitMQ/Mailhog
из compose — образы `php-auth-fullstack-php`/`php-auth-fullstack-notification` появляются после
`docker compose build` (нужны ради `pdo_pgsql`/`ext-mongodb`, которых нет в хостовом PHP).

**auth-service** (57 тестов: 46 юнит + 11 интеграционных):

```bash
cd backend
docker run --rm -v "$PWD:/app" -w /app php-auth-fullstack-php php vendor/bin/phpunit --testsuite Unit

docker run --rm -v "$PWD:/app" -w /app --network php-auth-fullstack_network2 \
  -e TEST_DB_HOST=postgres -e TEST_DB_PORT=5432 -e TEST_DB_PASSWORD="$POSTGRES_PASSWORD" \
  php-auth-fullstack-php php vendor/bin/phpunit
```

**notification-service** (23 теста: 15 юнит + 8 интеграционных — включая полный цикл
retry → retry → DLQ против настоящих RabbitMQ/MongoDB, с укороченными TTL специально для теста):

```bash
cd notification-service
docker run --rm -v "$PWD:/app" -w /app --network php-auth-fullstack_network2 \
  -e TEST_MONGO_URI=mongodb://mongo:27017 \
  -e TEST_RABBITMQ_HOST=rabbitmq -e TEST_RABBITMQ_USER=guest -e TEST_RABBITMQ_PASSWORD=guest \
  -e TEST_MAILHOG_HOST=mailhog -e TEST_MAILHOG_URL=http://mailhog:8025 \
  php-auth-fullstack-notification php vendor/bin/phpunit
```

Без поднятой инфраструктуры интеграционные тесты **скипаются** (`markTestSkipped`), а не падают —
можно гонять `--testsuite Unit` совсем без Docker.

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
добавлении новых переменных. `JWT_SECRET` в обоих файлах сервисов обязан совпадать — иначе
notification-service не сможет проверить токены, выданные auth-service.

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
