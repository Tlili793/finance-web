<?php

namespace App\Service\Testing;

use App\Entity\InsurancePackage;

class InsurancePackageTestService extends AbstractEntityTestService
{
    public function create(
        string $name = 'Basic Car Insurance',
        string $assetType = 'Vehicle',
        string $basePrice = '500.00',
        string $riskMultiplier = '1.20',
        int $durationMonths = 12
    ): InsurancePackage {
        $package = new InsurancePackage();
        $package->setName($name);
        $package->setAssetType($assetType);
        $package->setBasePrice($basePrice);
        $package->setRiskMultiplier($riskMultiplier);
        $package->setDurationMonths($durationMonths);
        $package->setIsActive(true);
        
        $this->persist($package);
        return $package;
    }
}
