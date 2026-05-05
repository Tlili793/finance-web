<?php

namespace App\Tests\Service;

use App\Entity\InsuredAsset;
use App\Service\Manager\InsuredAssetManager;
use PHPUnit\Framework\TestCase;

class InsuredAssetManagerTest extends TestCase
{
    private InsuredAssetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InsuredAssetManager();
    }

    private function validAsset(): InsuredAsset
    {
        $asset = new InsuredAsset();
        $asset->setReference('CAR-2024-001');
        $asset->setType('Vehicle');
        $asset->setDeclaredValue('25000.00');
        $asset->setManufactureDate(new \DateTime('2020-06-15'));
        return $asset;
    }

    public function testValidAssetReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validAsset()));
    }

    public function testBlankReferenceThrows(): void
    {
        $asset = $this->validAsset()->setReference('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/reference must not be blank/i');
        $this->manager->validate($asset);
    }

    public function testBlankTypeThrows(): void
    {
        $asset = $this->validAsset()->setType('  ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/type must not be blank/i');
        $this->manager->validate($asset);
    }

    public function testZeroDeclaredValueThrows(): void
    {
        $asset = $this->validAsset()->setDeclaredValue('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/declared value must be greater than 0/i');
        $this->manager->validate($asset);
    }

    public function testNullManufactureDateThrows(): void
    {
        $asset = new InsuredAsset();
        $asset->setReference('REF-001');
        $asset->setType('Vehicle');
        $asset->setDeclaredValue('5000.00');
        // manufactureDate is null by default

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/manufacture date is required/i');
        $this->manager->validate($asset);
    }

    public function testFutureManufactureDateThrows(): void
    {
        $asset = $this->validAsset()->setManufactureDate(new \DateTime('+1 year'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be in the future/i');
        $this->manager->validate($asset);
    }
}
