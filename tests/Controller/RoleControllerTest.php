<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RoleControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        
        $container = static::getContainer();
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $user = $userFactory->create(email: 'role.' . uniqid() . '@test.com');
        
        $client->loginUser($user);
        
        $client->request('GET', '/role');
        self::assertResponseIsSuccessful();
    }
}
