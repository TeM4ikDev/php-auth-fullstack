<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AdminUpdateUserDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseFactory;
use App\Resource\UserResource;
use App\Service\UserManagementService;
use InvalidArgumentException;

final class AdminUserController
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly UserManagementService $users,
        private readonly ResponseFactory $response,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) ($request->query('page') ?? 1));
        $perPage = (int) ($request->query('perPage') ?? self::DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $list = $this->users->list($page, $perPage, $request->query('search'));

        return $this->response->ok(UserResource::collection($list));
    }

    public function show(Request $request): Response
    {
        return $this->response->ok(UserResource::fromDto($this->users->find($this->userId($request))));
    }

    public function update(Request $request): Response
    {
        $updated = $this->users->update(
            $this->currentUser($request),
            $this->userId($request),
            AdminUpdateUserDto::fromArray($request->json()),
        );

        return $this->response->ok(UserResource::fromDto($updated));
    }

    public function ban(Request $request): Response
    {
        $payload = $request->json();

        if (!is_bool($payload['banned'] ?? null)) {
            throw new InvalidArgumentException('Field "banned" must be a boolean.');
        }

        $updated = $this->users->setBanned($this->currentUser($request), $this->userId($request), $payload['banned']);

        return $this->response->ok(UserResource::fromDto($updated));
    }

    public function setRole(Request $request): Response
    {
        $payload = $request->json();
        $role = UserRole::tryFrom(is_string($payload['role'] ?? null) ? strtoupper(trim($payload['role'])) : '');

        if ($role === null) {
            throw new InvalidArgumentException('Invalid role.');
        }

        $updated = $this->users->setRole($this->currentUser($request), $this->userId($request), $role);

        return $this->response->ok(UserResource::fromDto($updated));
    }

    private function userId(Request $request): int
    {
        $id = $request->routeParam('id');

        if ($id === null || !ctype_digit($id)) {
            throw new InvalidArgumentException('Invalid user id.');
        }

        return (int) $id;
    }

    private function currentUser(Request $request): UserDto
    {
        $user = $request->attribute('user');

        if (!$user instanceof UserDto) {
            throw new UnauthenticatedException();
        }

        return $user;
    }
}
