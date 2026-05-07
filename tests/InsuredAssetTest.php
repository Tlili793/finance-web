<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InsuredAssetTest extends KernelTestCase
{
    public function testAssetPersistence(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $assetFactory = $container->get(\App\Service\Testing\InsuredAssetTestService::class);
        
        $email = 'asset.owner.' . uniqid() . '@test.com';
        $owner = $userFactory->create(email: $email);
        $asset = $assetFactory->create($owner, reference: 'TEST-REF-999');

        $this->assertNotNull($asset->getId());
        $this->assertSame($owner, $asset->getUser());
        $this->assertEquals('TEST-REF-999', $asset->getReference());
    }
}
