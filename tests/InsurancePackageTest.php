<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InsurancePackageTest extends KernelTestCase
{
    public function testPackageCreation(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        /** @var \App\Service\Testing\InsurancePackageTestService $packageFactory */
        $packageFactory = $container->get(\App\Service\Testing\InsurancePackageTestService::class);
        
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $creator = $userFactory->create(email: 'creator.' . uniqid() . '@test.com');

        $package = $packageFactory->create(
            creator: $creator,
            name: 'Integration Test Package',
            assetType: 'Vehicle',
            basePrice: '150.00'
        );

        $this->assertNotNull($package->getId());
        $this->assertEquals('Integration Test Package', $package->getName());
        $this->assertEquals('150.00', $package->getBasePrice());
    }
}
