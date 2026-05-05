<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIntegrationTest extends KernelTestCase
{
    public function testUserLifecycle(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        
        $email = 'unique.' . uniqid() . '@test.com';
        $user1 = $userFactory->create(email: $email, name: 'Integration User');
        
        $this->assertNotNull($user1->getId());
        
        // Verify findOrCreate returns same instance
        $user2 = $userFactory->findOrCreate(email: $email);
        $this->assertSame($user1->getId()->toBinary(), $user2->getId()->toBinary());
    }
}
