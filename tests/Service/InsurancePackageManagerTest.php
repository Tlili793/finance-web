<?php

namespace App\Tests\Service;

use App\Entity\InsurancePackage;
use App\Service\Manager\InsurancePackageManager;
use PHPUnit\Framework\TestCase;

class InsurancePackageManagerTest extends TestCase
{
    private InsurancePackageManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InsurancePackageManager();
    }

    private function validPackage(): InsurancePackage
    {
        $pkg = new InsurancePackage();
        $pkg->setName('Gold Car Cover');
        $pkg->setAssetType('Vehicle');
        $pkg->setBasePrice('500.00');
        $pkg->setRiskMultiplier('1.20');
        $pkg->setDurationMonths(12);
        return $pkg;
    }

    public function testValidPackageReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validPackage()));
    }

    public function testBlankNameThrows(): void
    {
        $pkg = $this->validPackage()->setName('   ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/name must not be blank/i');
        $this->manager->validate($pkg);
    }

    public function testBlankAssetTypeThrows(): void
    {
        $pkg = $this->validPackage()->setAssetType('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/asset type/i');
        $this->manager->validate($pkg);
    }

    public function testZeroBasePriceThrows(): void
    {
        $pkg = $this->validPackage()->setBasePrice('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/base price/i');
        $this->manager->validate($pkg);
    }

    public function testNegativeRiskMultiplierThrows(): void
    {
        $pkg = $this->validPackage()->setRiskMultiplier('-1.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/multiplier/i');
        $this->manager->validate($pkg);
    }

    public function testZeroDurationThrows(): void
    {
        $pkg = $this->validPackage()->setDurationMonths(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at least 1 month/i');
        $this->manager->validate($pkg);
    }
}
