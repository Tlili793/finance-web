<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContractRequestTest extends KernelTestCase
{
    public function testRequestWorkflowPersistence(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $userFactory = $container->get(\App\Service\Testing\UserTestService::class);
        $assetFactory = $container->get(\App\Service\Testing\InsuredAssetTestService::class);
        $packageFactory = $container->get(\App\Service\Testing\InsurancePackageTestService::class);
        $requestFactory = $container->get(\App\Service\Testing\ContractRequestTestService::class);
        
        $user = $userFactory->create(email: 'requester@test.com');
        $asset = $assetFactory->create($user);
        $package = $packageFactory->create();
        
        $request = $requestFactory->create($user, $asset, $package, status: 'PENDING');

        $this->assertNotNull($request->getId());
        $this->assertSame($user, $request->getUser());
        $this->assertSame($asset, $request->getAsset());
        $this->assertSame($package, $request->getPackage());
    }
}
