<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\PasswordService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PasswordServiceTest extends TestCase
{
    #[Test]
    public function it_never_stores_the_plain_password(): void
    {
        $hash = (new PasswordService())->hash('secret123');

        self::assertNotSame('secret123', $hash);
        self::assertStringStartsWith('$2y$', $hash);
    }

    #[Test]
    public function it_verifies_the_correct_password(): void
    {
        $service = new PasswordService();

        self::assertTrue($service->verify('secret123', $service->hash('secret123')));
    }

    #[Test]
    public function it_rejects_a_wrong_password(): void
    {
        $service = new PasswordService();

        self::assertFalse($service->verify('wrong-password', $service->hash('secret123')));
    }

    #[Test]
    public function it_produces_a_different_hash_for_the_same_password(): void
    {
        $service = new PasswordService();

        self::assertNotSame($service->hash('secret123'), $service->hash('secret123'));
    }
}
