<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use App\Dto\AdminUpdateUserDto;
use App\Dto\ChangePasswordDto;
use App\Dto\LoginDto;
use App\Dto\UpdateProfileDto;
use App\Enum\UserRole;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProfileDtoTest extends TestCase
{
    #[Test]
    public function login_normalizes_email(): void
    {
        self::assertSame('a@b.com', LoginDto::fromArray(['email' => ' A@B.com ', 'password' => 'x'])->email);
    }

    #[Test]
    public function login_rejects_empty_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginDto::fromArray(['email' => '', 'password' => '']);
    }

    #[Test]
    public function update_profile_clears_an_empty_phone(): void
    {
        $dto = UpdateProfileDto::fromArray(['name' => 'Artem', 'email' => 'a@b.com', 'phone' => '  ']);

        self::assertNull($dto->phone);
    }

    #[Test]
    public function change_password_rejects_a_short_new_password(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChangePasswordDto::fromArray(['currentPassword' => 'secret123', 'newPassword' => 'short']);
    }

    #[Test]
    public function change_password_rejects_an_unchanged_password(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChangePasswordDto::fromArray(['currentPassword' => 'secret123', 'newPassword' => 'secret123']);
    }

    #[Test]
    public function change_password_requires_the_current_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChangePasswordDto::fromArray(['currentPassword' => '', 'newPassword' => 'newsecret123']);
    }

    #[Test]
    public function admin_update_parses_role_case_insensitively(): void
    {
        $dto = AdminUpdateUserDto::fromArray([
            'name' => 'Artem',
            'email' => 'a@b.com',
            'role' => 'analyst',
            'banned' => true,
        ]);

        self::assertSame(UserRole::Analyst, $dto->role);
        self::assertTrue($dto->banned);
    }

    #[Test]
    public function admin_update_rejects_an_unknown_role(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdminUpdateUserDto::fromArray([
            'name' => 'Artem',
            'email' => 'a@b.com',
            'role' => 'SUPERUSER',
            'banned' => false,
        ]);
    }

    #[Test]
    public function admin_update_rejects_a_non_boolean_banned_flag(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdminUpdateUserDto::fromArray([
            'name' => 'Artem',
            'email' => 'a@b.com',
            'role' => 'CUSTOMER',
            'banned' => 'yes',
        ]);
    }
}
