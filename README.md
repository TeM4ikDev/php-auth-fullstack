# php-auth-fullstack

React (Vite) + PHP за nginx, PostgreSQL, JWT-авторизация.

## Запуск через Docker

```bash
docker compose up -d --build
docker compose exec php php /var/www/migrate.php
```

Сайт: `http://localhost:8081` (порт задан в `compose.yaml`, сервис `nginx`).
Adminer (БД): `http://localhost:1500`.

## Запуск без Docker (бэкенд)

```bash
cd backend
composer install
composer serve      # http://localhost:8000
composer migrate
```

`.env` в корне репозитория читают и Docker Compose, и `Config` бэкенда — секреты не дублируются.

## Фронтенд отдельно (dev-режим)

```bash
cd client
npm install
npm run dev
```

## На VPS

Порт из `compose.yaml` (по умолчанию 8081) должен быть открыт в фаерволе:

```bash
sudo ufw allow 8081/tcp
```

Если хостинг облачный — порт также открывается в security group/панели провайдера отдельно от `ufw`.
