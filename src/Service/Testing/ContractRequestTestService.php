<?php

namespace App\Service\Testing;

use App\Entity\ContractRequest;
use App\Entity\InsurancePackage;
use App\Entity\InsuredAsset;
use App\Entity\User;

class ContractRequestTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        InsuredAsset $asset,
        InsurancePackage $package,
        string $status = 'PENDING',
        string $calculatedPremium = '600.00'
    ): ContractRequest {
        $request = new ContractRequest();
        $request->setUser($user);
        $request->setAsset($asset);
        $request->setPackage($package);
        $request->setStatus($status);
        $request->setCalculatedPremium($calculatedPremium);
        
        $this->persist($request);
        return $request;
    }
}
