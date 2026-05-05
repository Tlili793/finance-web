<?php

namespace App\Tests\Service;

use App\Entity\ContractRequest;
use App\Entity\InsurancePackage;
use App\Entity\InsuredAsset;
use App\Entity\User;
use App\Service\Manager\ContractRequestManager;
use PHPUnit\Framework\TestCase;

class ContractRequestManagerTest extends TestCase
{
    private ContractRequestManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ContractRequestManager();
    }

    private function validRequest(): ContractRequest
    {
        $user    = $this->createMock(User::class);
        $asset   = $this->createMock(InsuredAsset::class);
        $package = $this->createMock(InsurancePackage::class);

        $request = new ContractRequest();
        $request->setUser($user);
        $request->setAsset($asset);
        $request->setPackage($package);
        $request->setStatus('PENDING');
        $request->setCalculatedPremium('600.00');
        return $request;
    }

    public function testValidRequestReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validRequest()));
    }

    public function testMissingUserThrows(): void
    {
        $request = $this->validRequest();
        $request->setUser(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a user/i');
        $this->manager->validate($request);
    }

    public function testMissingAssetThrows(): void
    {
        $request = $this->validRequest();
        $request->setAsset(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/insured asset/i');
        $this->manager->validate($request);
    }

    public function testMissingPackageThrows(): void
    {
        $request = $this->validRequest();
        $request->setPackage(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/insurance package/i');
        $this->manager->validate($request);
    }

    public function testInvalidStatusThrows(): void
    {
        $request = $this->validRequest();
        $request->setStatus('UNKNOWN');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/PENDING, APPROVED, REJECTED, SIGNED, CANCELLED/i');
        $this->manager->validate($request);
    }

    public function testZeroPremiumThrows(): void
    {
        $request = $this->validRequest();
        $request->setCalculatedPremium('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($request);
    }

    public function testNullPremiumIsAllowed(): void
    {
        $request = $this->validRequest();
        $request->setCalculatedPremium(null);
        $this->assertTrue($this->manager->validate($request));
    }
}
