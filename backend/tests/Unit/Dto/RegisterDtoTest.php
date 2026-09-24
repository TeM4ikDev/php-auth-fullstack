<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use App\Dto\RegisterDto;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegisterDtoTest extends TestCase
{
    #[Test]
    public function it_normalizes_input(): void
    {
        $dto = RegisterDto::fromArray([
            'name' => '  Artem  ',
            'email' => '  Artem@Example.COM ',
            'phone' => ' +375291234567 ',
            'password' => 'secret123',
        ]);

        self::assertSame('Artem', $dto->name);
        self::assertSame('artem@example.com', $dto->email);
        self::assertSame('+375291234567', $dto->phone);
    }

    #[Test]
    public function phone_is_optional(): void
    {
        $dto = RegisterDto::fromArray([
            'name' => 'Artem',
            'email' => 'artem@example.com',
            'password' => 'secret123',
        ]);

        self::assertNull($dto->phone);
    }

    /** @param array<string, mixed> $payload */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_rejects_invalid_payloads(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        RegisterDto::fromArray($payload);
    }

    public static function invalidPayloads(): array
    {
        $valid = ['name' => 'Artem', 'email' => 'artem@example.com', 'password' => 'secret123'];

        return [
            'empty name' => [[...$valid, 'name' => '   ']],
            'missing name' => [['email' => 'a@b.com', 'password' => 'secret123']],
            'malformed email' => [[...$valid, 'email' => 'not-an-email']],
            'short password' => [[...$valid, 'password' => 'short']],
            'malformed phone' => [[...$valid, 'phone' => 'abc-phone']],
        ];
    }
}
