<?php

namespace App\Service\Testing;

use App\Entity\ContractRequest;
use App\Entity\InsuredContract;

class InsuredContractTestService extends AbstractEntityTestService
{
    public function create(
        ContractRequest $request,
        string $contractNumber = 'POL-2024-0001',
        string $status = 'ACTIVE'
    ): InsuredContract {
        $contract = new InsuredContract();
        $contract->setRequest($request);
        $contract->setContractNumber($contractNumber);
        $contract->setStatus($status);
        $contract->setValidFrom(new \DateTime());
        $contract->setValidUntil(new \DateTime('+1 year'));
        
        $this->persist($contract);
        return $contract;
    }
}
