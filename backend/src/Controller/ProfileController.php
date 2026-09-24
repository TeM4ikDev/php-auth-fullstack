<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ChangePasswordDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseFactory;
use App\Resource\UserResource;
use App\Service\ProfileService;

final class ProfileController
{
    public function __construct(
        private readonly ProfileService $profile,
        private readonly ResponseFactory $response,
    ) {
    }

    public function show(Request $request): Response
    {
        return $this->response->ok(UserResource::fromDto($this->currentUser($request)));
    }

    public function update(Request $request): Response
    {
        $user = $this->currentUser($request);
        $updated = $this->profile->update($user, UpdateProfileDto::fromArray($request->json()));

        return $this->response->ok(UserResource::fromDto($updated));
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->currentUser($request);
        $this->profile->changePassword($user, ChangePasswordDto::fromArray($request->json()));

        return $this->response->noContent();
    }

    public function destroy(Request $request): Response
    {
        $this->profile->delete($this->currentUser($request));

        return $this->response->noContent();
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
