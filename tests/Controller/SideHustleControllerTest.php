<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SideHustleControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        
        $container = static::getContainer();
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $user = $userFactory->create(email: 'side.' . uniqid() . '@test.com');
        
        $client->loginUser($user);
        
        $client->request('GET', '/finance/side-hustle/');
        self::assertResponseIsSuccessful();
    }
}
