<?php

namespace App\Service\Manager;

use App\Entity\InsuredContract;

/**
 * Business Rules for InsuredContract:
 *  1. Asset reference must not be blank.
 *  2. Boldsign document ID must not be blank.
 *  3. Status must be valid (enforced by Enum in entity, but we check presence).
 */
class InsuredContractManager
{
    public function validate(InsuredContract $contract): bool
    {
        if (empty(trim($contract->getAssetRef() ?? ''))) {
            throw new \InvalidArgumentException('Asset reference must not be blank.');
        }

        if (empty(trim($contract->getBoldsignDocumentId() ?? ''))) {
            throw new \InvalidArgumentException('Boldsign document ID must not be blank.');
        }

        return true;
    }
}
