<?php

namespace App\Service\Testing;

use App\Entity\InsuredAsset;
use App\Entity\User;

class InsuredAssetTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $reference = 'REF-CAR-001',
        string $type = 'Vehicle',
        string $declaredValue = '25000.00',
        string $brand = 'Toyota'
    ): InsuredAsset {
        $asset = new InsuredAsset();
        $asset->setUser($user);
        $asset->setReference($reference);
        $asset->setType($type);
        $asset->setDeclaredValue($declaredValue);
        $asset->setBrand($brand);
        $asset->setManufactureDate(new \DateTime('-2 years'));
        
        $this->persist($asset);
        return $asset;
    }
}
