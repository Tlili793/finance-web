<?php

namespace App\Tests\Service;

use App\Entity\Role;
use App\Service\Manager\RoleManager;
use PHPUnit\Framework\TestCase;

class RoleManagerTest extends TestCase
{
    private RoleManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RoleManager();
    }

    private function validRole(): Role
    {
        $role = new Role();
        $role->setRoleName('ROLE_ADMIN');
        return $role;
    }

    public function testValidRoleReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validRole()));
    }

    public function testBlankRoleNameThrows(): void
    {
        $role = $this->validRole();
        $role->setRoleName('   ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name must not be blank/i');
        $this->manager->validate($role);
    }
}
