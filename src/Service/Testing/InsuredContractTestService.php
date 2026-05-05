<?php

namespace App\Service\Testing;

use App\Entity\InsuredContract;

class InsuredContractTestService extends AbstractEntityTestService
{
    public function create(
        string $assetRef = 'CAR-2024-TEST',
        string $documentId = 'boldsign-doc-123',
        \App\Enum\InsuredContractStatus $status = \App\Enum\InsuredContractStatus::NOT_SIGNED
    ): InsuredContract {
        $contract = new InsuredContract();
        $contract->setAssetRef($assetRef);
        $contract->setBoldsignDocumentId($documentId);
        $contract->setStatus($status);
        
        $this->persist($contract);
        return $contract;
    }
}
