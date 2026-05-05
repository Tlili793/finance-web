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
        
        $package = $packageFactory->create(
            name: 'Integration Test Package',
            assetType: 'Vehicle',
            basePrice: '150.00'
        );

        $this->assertNotNull($package->getId());
        $this->assertEquals('Integration Test Package', $package->getName());
        $this->assertEquals('150.00', $package->getBasePrice());
    }
}
