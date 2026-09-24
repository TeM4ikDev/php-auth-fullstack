<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Service\AccessPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccessPolicyTest extends TestCase
{
    private const SELF_ID = 1;

    private const OTHER_ID = 2;

    #[Test]
    public function anyone_may_view_and_update_their_own_profile(): void
    {
        $policy = new AccessPolicy();
        $customer = $this->user(self::SELF_ID, UserRole::Customer);

        self::assertTrue($policy->canViewUser($customer, self::SELF_ID));
        self::assertTrue($policy->canUpdateUser($customer, self::SELF_ID));
    }

    #[Test]
    public function a_customer_may_not_touch_someone_else(): void
    {
        $policy = new AccessPolicy();
        $customer = $this->user(self::SELF_ID, UserRole::Customer);

        self::assertFalse($policy->canViewUser($customer, self::OTHER_ID));
        self::assertFalse($policy->canUpdateUser($customer, self::OTHER_ID));
    }

    #[Test]
    public function an_analyst_has_no_management_rights(): void
    {
        $policy = new AccessPolicy();
        $analyst = $this->user(self::SELF_ID, UserRole::Analyst);

        self::assertFalse($policy->canUpdateUser($analyst, self::OTHER_ID));
        self::assertFalse($policy->canBan($analyst, self::OTHER_ID));
        self::assertFalse($policy->canChangeRole($analyst, self::OTHER_ID));
    }

    #[Test]
    public function an_admin_manages_other_users(): void
    {
        $policy = new AccessPolicy();
        $admin = $this->user(self::SELF_ID, UserRole::Admin);

        self::assertTrue($policy->canViewUser($admin, self::OTHER_ID));
        self::assertTrue($policy->canUpdateUser($admin, self::OTHER_ID));
        self::assertTrue($policy->canBan($admin, self::OTHER_ID));
        self::assertTrue($policy->canChangeRole($admin, self::OTHER_ID));
    }

    /** Иначе администратор способен заблокировать сам себе доступ без пути назад. */
    #[Test]
    public function an_admin_may_not_ban_or_demote_themselves(): void
    {
        $policy = new AccessPolicy();
        $admin = $this->user(self::SELF_ID, UserRole::Admin);

        self::assertFalse($policy->canBan($admin, self::SELF_ID));
        self::assertFalse($policy->canChangeRole($admin, self::SELF_ID));
    }

    private function user(int $id, UserRole $role): UserDto
    {
        return new UserDto($id, 'User', null, 'user@example.com', 'hash', $role, false, null, null, '2026-01-01', null);
    }
}
