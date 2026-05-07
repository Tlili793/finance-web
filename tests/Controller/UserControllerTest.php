<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        
        // Setup Admin User
        $container = static::getContainer();
        $roleFactory = $container->get(\App\Service\Testing\RoleTestService::class);
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        
        $adminRole = $roleFactory->create(name: 'ROLE_ADMIN');
        $adminEmail = 'admin.' . uniqid() . '@test.com';
        $adminUser = $userFactory->create(email: $adminEmail, roles: ['ROLE_ADMIN']);
        $adminUser->setRole($adminRole);
        
        $client->loginUser($adminUser);
        
        $client->request('GET', '/admin/users');
        self::assertResponseIsSuccessful();
    }
}
