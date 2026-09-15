# Конвенции проекта

Каждый пункт — с примером «было / стало» из этого
же репозитория.

## Часть 1. Правила

### 1. Внедрение зависимостей только через конструктор

Никаких `new` внутри классов и `?Type $x = null` с ленивым созданием по умолчанию —
зависимость должна приходить снаружи, иначе класс нельзя подменить в тестах.

```php
// было — AuthController.php
private $auth;
public function __construct(?AuthService $auth = null, ?AuthMiddleware $middleware = null)
{
    $this->auth = $auth;
    $this->middleware = $middleware ?? new AuthMiddleware();
}

// стало
public function __construct(
    private readonly AuthService $auth,
    private readonly AuthMiddleware $middleware,
) {}
```

Все объекты собирает DI-контейнер (`src/Container/Container.php`), явные привязки — в
`config/services.php`.

### 2. Полная типизация

Свойства, аргументы и возвраты — типизированы; `readonly` там, где объект не меняется
после создания; `declare(strict_types=1)` в каждом файле.

```php
// было — RegisterDto.php
public $name;
public $email;
public $password;

// стало
public function __construct(
    public readonly string $name,
    public readonly string $email,
    public readonly string $password,
) {}
```

### 3. Тонкий index.php

`public/index.php` — это только автозагрузка и запуск приложения, вся инфраструктура
(CORS, rate limit, сборка зависимостей, таблица роутов, обработка ошибок) уезжает в
`Kernel` и `routes/api.php`.

```php
// стало — public/index.php целиком
require dirname(__DIR__) . '/vendor/autoload.php';

Kernel::boot(dirname(__DIR__))->run();
```

### 4. Один класс — одна ответственность (SRP для конфигов)

`Config` не должен одновременно читать `.env`, собирать DSN и хранить настройки JWT.

```php
// было — Config.php отвечал за всё сразу
Config::get() / Config::database() / Config::jwtSecret() / Config::jwtTtl()

// стало — раздельные классы
EnvLoader          // только чтение .env
Config             // только доступ к значениям по ключу
DatabaseConfigFactory // сборка DatabaseConfig (dsn/user/password) из DATABASE_URL
JwtConfig          // secret/ttl/algorithm как отдельный value object
```

### 5. Тонкие контроллеры

Никаких приватных `body()/error()/fail()` внутри контроллера — эти обязанности у
`Request` и `ResponseFactory`. Контроллер возвращает объект `Response`, а не сырой
массив; `json_encode` вызывается один раз, внутри `Response`.

```php
// было — AuthController.php
public function register(): array
{
    try {
        $dto = RegisterDto::fromArray($this->body());
        return ['status' => 201, 'body' => $this->auth()->register($dto)];
    } catch (InvalidArgumentException $e) {
        return $this->error($e->getMessage(), 422);
    } catch (Throwable $e) {
        return $this->fail($e);
    }
}

// стало
public function register(Request $request): Response
{
    $dto = RegisterDto::fromArray($request->json());
    $result = $this->auth->register($dto);

    return $this->response->created(AuthResource::fromResult($result));
}
```

Обработку исключений берёт на себя `ExceptionHandler` — try/catch в контроллере не нужен.

### 6. Репозитории инкапсулируют весь SQL

Сервисы не знают про PDO и SQL-запросы — только про интерфейс репозитория.
Сигнатура метода репозитория: 1–2 скалярных аргумента, при большем числе — DTO на входе;
на выходе — всегда DTO, не массив.

```php
// было — AuthService.php
$statement = $this->db->prepare(Sql::INSERT_USER);
$statement->execute([...]);

// стало
interface UserRepositoryInterface
{
    public function findById(int $id): ?UserDto;
    public function findByEmail(string $email): ?UserDto;
    public function create(CreateUserDto $dto): UserDto;
}
```

`AuthService` работает только с `UserRepositoryInterface`.

### 7. DTO и Resource

Правило: «много данных → DTO». DTO переносит данные между слоями; Resource превращает
DTO в JSON-представление для конкретного ответа API. Вызывается Resource из контроллера.

```php
// было — AuthService.php
public function publicUser(array $user): array
{
    return ['id' => (string) $user['id'], 'name' => $user['name'], ...];
}

// стало
final class UserResource
{
    public static function fromDto(UserDto $user): array
    {
        return ['id' => (string) $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role->value];
    }
}
```

## Часть 2. Общие практики хорошего тона

### Архитектура и зависимости

- **Зависеть от абстракций, а не от реализаций** (DIP): сервис знает
  `UserRepositoryInterface`, а не `PdoUserRepository`. Замена БД не должна требовать
  правки сервиса.
- **Никакого глобального состояния.** Статические `Config::$env` и
  `Database::$connection` заменяются синглтонами контейнера — статический класс нельзя
  подменить в тесте и нельзя параллельно сконфигурировать по-разному.
- **Слои не перепрыгивают друг через друга**: контроллер → сервис → репозиторий.
  Контроллер не трогает PDO напрямую, репозиторий не знает про HTTP-запрос.
- **HTTP-статусы не текут в домен.** Было:
  `throw new RuntimeException('Email already used', 409)` внутри `AuthService` — код
  ответа зашит в бизнес-логику. Правильно: бросать доменное исключение
  (`EmailAlreadyTakenException`), а перевод в HTTP-статус делает `ExceptionHandler`.
- **`final` по умолчанию.** Наследование открывается осознанно, когда оно реально нужно.

### Стиль кода

- PSR-12 (форматирование) и PSR-4 (автозагрузка).
- Фигурные скобки всегда, даже у однострочных `if`. Было:
  `if ($user === null) throw new RuntimeException('Error', 500);` — легко потерять
  вторую инструкцию при правке.
- Guard clauses вместо вложенных `if/else` — ранний выход читается быстрее лестницы
  условий.
- Имена отражают роль: интерфейс — `UserRepositoryInterface`, реализация —
  `PdoUserRepository`; методы — глаголы (`findByEmail`, `hash`, `verify`).
- Комментарий объясняет *почему* сделано именно так, а не пересказывает код построчно.
- Магические литералы выносим в константы/enum: строка `'USER'` → `UserRole::User`,
  лимиты запросов (5, 60, 86400) → конфиг, а не хардкод в коде.

### Обработка ошибок

- Исключения вместо кодов возврата и «пустых» значений вроде `null`/`false` без
  объяснения причины.
- Единый контракт ошибки API: `{"error": "..."}`, для валидации — `{"error": "...", "errors": {...}}`.
  Фронт разбирает ошибки одним способом, а не по каждому эндпоинту отдельно.
- Исключения ловятся в одном месте (`ExceptionHandler`), а не в `try/catch` на каждый
  метод контроллера.
- Не подавлять ошибки оператором `@` (было в `RateLimiterService`: `@mkdir(...)`,
  `@fopen(...)`) — либо обработать сбой явно, либо дать ему упасть с понятной трассировкой.

### Безопасность

- Секреты — только из окружения, без «удобных» значений по умолчанию. Было:
  `Config::jwtSecret()` возвращал `'dev-secret-change-me'`, если переменная не задана —
  приложение тихо работало на предсказуемом секрете. Правильно: падать при старте, если
  `JWT_SECRET` не установлен.
- `.env` с реальными значениями не попадает в git — в репозитории только `.env.example`
  с плейсхолдерами.
- Наружу не должны утекать внутренности ошибок. Было:
  `$send(500, ['error' => 'err: ' . $e->getMessage()])` — клиент получал текст ошибки
  PDO вместе с DSN. Подробности — в лог (`error_log`), клиенту — общее сообщение.
- Пароли, хеши и токены никогда не логируются и не попадают в тело ответа;
  `password_hash` не должен покидать репозиторий дальше внутреннего `UserDto`.
- Только подготовленные выражения (`PDO::prepare`), никакой конкатенации значений в SQL.

### Фронтенд

- Строгий TypeScript, без `any`. Типы ответов API держим в одном месте (`client/src/types`),
  а не дублируем в каждом компоненте.
- Компонент отвечает за отображение; логика запросов и side-эффектов — в хуках и сервисах.
- Единая схема именования файлов компонентов (сейчас смесь `Header.tsx`, `googleButton.tsx`,
  `auth.service.ts` — выбрать один стиль и держаться его).
- В клиентский код не попадают секреты: всё, что передаётся через `VITE_*`, считается
  публичным.
