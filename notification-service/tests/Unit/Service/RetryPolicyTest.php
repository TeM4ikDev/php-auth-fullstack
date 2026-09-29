<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\RetryConfig;
use App\Service\RetryPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RetryPolicyTest extends TestCase
{
    #[Test]
    public function it_returns_the_configured_delay_for_each_attempt(): void
    {
        $policy = $this->policy();

        self::assertSame(5_000, $policy->delayFor(1));
        self::assertSame(25_000, $policy->delayFor(2));
        self::assertSame(125_000, $policy->delayFor(3));
    }

    #[Test]
    public function it_is_exhausted_once_the_attempt_exceeds_max_attempts(): void
    {
        $policy = $this->policy();

        self::assertFalse($policy->isExhausted(3));
        self::assertTrue($policy->isExhausted(4));
    }

    #[Test]
    public function delay_for_is_null_once_exhausted(): void
    {
        self::assertNull($this->policy()->delayFor(4));
    }

    private function policy(): RetryPolicy
    {
        return new RetryPolicy(new RetryConfig(3, [5_000, 25_000, 125_000]));
    }
}
