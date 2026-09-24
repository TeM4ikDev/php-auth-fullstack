<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Dto\CreateUserDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Repository\Exception\EmailAlreadyTakenException;
use App\Repository\PdoUserRepository;
use PHPUnit\Framework\Attributes\Test;

final class PdoUserRepositoryTest extends DatabaseTestCase
{
    private PdoUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PdoUserRepository($this->pdo);
    }

    #[Test]
    public function it_creates_a_user_with_customer_role_by_default(): void
    {
        $user = $this->createUser();

        self::assertGreaterThan(0, $user->id);
        self::assertSame(UserRole::Customer, $user->role);
        self::assertFalse($user->banned);
        self::assertNull($user->deletedAt);
        self::assertSame('+375291234567', $user->phone);
    }

    #[Test]
    public function it_rejects_a_duplicate_email(): void
    {
        $this->createUser(email: 'duplicate@example.com');

        $this->expectException(EmailAlreadyTakenException::class);

        $this->createUser(email: 'duplicate@example.com');
    }

    #[Test]
    public function it_finds_users_by_id_and_email(): void
    {
        $user = $this->createUser();

        self::assertSame($user->id, $this->repository->findById($user->id)?->id);
        self::assertSame($user->id, $this->repository->findByEmail($user->email)?->id);
        self::assertNull($this->repository->findByEmail('missing@example.com'));
    }

    #[Test]
    public function it_updates_the_profile(): void
    {
        $user = $this->createUser();

        $updated = $this->repository->update($user->id, UpdateProfileDto::fromArray([
            'name' => 'Renamed',
            'phone' => '+375299998877',
            'email' => 'renamed@example.com',
        ]));

        self::assertSame('Renamed', $updated->name);
        self::assertSame('+375299998877', $updated->phone);
        self::assertSame('renamed@example.com', $updated->email);
    }

    #[Test]
    public function it_updates_the_password_hash(): void
    {
        $user = $this->createUser();

        $this->repository->updatePassword($user->id, 'new-hash');

        self::assertSame('new-hash', $this->repository->findById($user->id)?->passwordHash);
    }

    /** Мягко удалённый пользователь не должен проходить логин. */
    #[Test]
    public function a_soft_deleted_user_disappears_from_lookups(): void
    {
        $user = $this->createUser();

        $this->repository->softDelete($user->id);

        self::assertNull($this->repository->findById($user->id));
        self::assertNull($this->repository->findByEmail($user->email));
    }

    /** Но админка обязана показывать его карточку. */
    #[Test]
    public function an_admin_lookup_still_sees_a_soft_deleted_user(): void
    {
        $user = $this->createUser();
        $this->repository->softDelete($user->id);

        $found = $this->repository->findByIdIncludingDeleted($user->id);

        self::assertNotNull($found);
        self::assertNotNull($found->deletedAt);
        self::assertTrue($found->isDeleted());
    }

    #[Test]
    public function it_toggles_the_ban_flag(): void
    {
        $user = $this->createUser();

        self::assertTrue($this->repository->setBanned($user->id, true)->banned);
        self::assertFalse($this->repository->setBanned($user->id, false)->banned);
    }

    #[Test]
    public function it_changes_the_role(): void
    {
        $user = $this->createUser();

        self::assertSame(UserRole::Analyst, $this->repository->setRole($user->id, UserRole::Analyst)->role);
        self::assertSame(UserRole::Admin, $this->repository->setRole($user->id, UserRole::Admin)->role);
    }

    #[Test]
    public function it_paginates_and_excludes_soft_deleted_users(): void
    {
        $this->pdo->exec('DELETE FROM users');

        $first = $this->createUser(email: 'one@example.com');
        $this->createUser(email: 'two@example.com');
        $this->createUser(email: 'three@example.com');
        $this->repository->softDelete($first->id);

        self::assertSame(2, $this->repository->countAll());
        self::assertCount(2, $this->repository->findAll(10, 0));
        self::assertCount(1, $this->repository->findAll(1, 0));
    }

    #[Test]
    public function it_searches_by_name_email_and_phone(): void
    {
        $this->pdo->exec('DELETE FROM users');
        $this->createUser(name: 'Findable Person', email: 'findable@example.com', phone: '+375291110000');
        $this->createUser(name: 'Another', email: 'another@example.com', phone: '+375292220000');

        self::assertSame(1, $this->repository->countAll('Findable'));
        self::assertSame(1, $this->repository->countAll('findable@'));
        self::assertSame(1, $this->repository->countAll('1110000'));
        self::assertSame(2, $this->repository->countAll('example.com'));
        self::assertSame(2, $this->repository->countAll('   '));
    }

    private function createUser(
        string $name = 'Test User',
        string $email = 'test@example.com',
        ?string $phone = '+375291234567',
        UserRole $role = UserRole::Customer,
    ): UserDto {
        return $this->repository->create(new CreateUserDto($name, $email, 'hashed-password', bin2hex(random_bytes(16)), $phone, $role));
    }
}
