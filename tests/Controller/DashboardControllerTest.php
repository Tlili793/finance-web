<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        
        $container = static::getContainer();
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $user = $userFactory->create(email: 'dashboard.' . uniqid() . '@test.com');
        
        $client->loginUser($user);
        
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }
}
