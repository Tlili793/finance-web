<?php

namespace App\Tests\Service;

use App\Entity\InsuredContract;
use App\Service\Manager\InsuredContractManager;
use PHPUnit\Framework\TestCase;

class InsuredContractManagerTest extends TestCase
{
    private InsuredContractManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InsuredContractManager();
    }

    private function validContract(): InsuredContract
    {
        $contract = new InsuredContract();
        $contract->setAssetRef('CAR-123');
        $contract->setBoldsignDocumentId('doc-555');
        return $contract;
    }

    public function testValidContractReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validContract()));
    }

    public function testBlankAssetRefThrows(): void
    {
        $contract = $this->validContract();
        $contract->setAssetRef('  ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/asset reference/i');
        $this->manager->validate($contract);
    }

    public function testBlankBoldsignIdThrows(): void
    {
        $contract = $this->validContract();
        $contract->setBoldsignDocumentId('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/boldsign document ID/i');
        $this->manager->validate($contract);
    }
}
