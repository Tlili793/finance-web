<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\Manager\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    private function validUser(): User
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('hashed_password_here');
        return $user;
    }

    public function testValidUserReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validUser()));
    }

    public function testBlankNameThrows(): void
    {
        $user = $this->validUser();
        $user->setName('  ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name is required/i');
        $this->manager->validate($user);
    }

    public function testInvalidNameFormatThrows(): void
    {
        $user = $this->validUser();
        $user->setName('John Doe 123');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/only contain letters and spaces/i');
        $this->manager->validate($user);
    }

    public function testInvalidEmailThrows(): void
    {
        $user = $this->validUser();
        $user->setEmail('invalid-email');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/valid email/i');
        $this->manager->validate($user);
    }

    public function testEmptyPasswordThrows(): void
    {
        $user = $this->validUser();
        $user->setPassword('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must have a password/i');
        $this->manager->validate($user);
    }
}
