<?php

namespace App\Service\Manager;

use App\Entity\ContractRequest;

/**
 * Business Rules for ContractRequest:
 *  1. User must be set.
 *  2. Asset must be set.
 *  3. Package must be set.
 *  4. Status must be one of: PENDING, APPROVED, REJECTED, SIGNED, CANCELLED.
 *  5. Calculated premium, if set, must be positive.
 */
class ContractRequestManager
{
    private const VALID_STATUSES = ['PENDING', 'APPROVED', 'REJECTED', 'SIGNED', 'CANCELLED'];

    public function validate(ContractRequest $request): bool
    {
        if ($request->getUser() === null) {
            throw new \InvalidArgumentException('Contract request must be linked to a user.');
        }

        if ($request->getAsset() === null) {
            throw new \InvalidArgumentException('Contract request must be linked to an insured asset.');
        }

        if ($request->getPackage() === null) {
            throw new \InvalidArgumentException('Contract request must be linked to an insurance package.');
        }

        if (!in_array($request->getStatus(), self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Status must be one of: %s.', implode(', ', self::VALID_STATUSES))
            );
        }

        $premium = $request->getCalculatedPremium();
        if ($premium !== null && (float) $premium <= 0) {
            throw new \InvalidArgumentException('Calculated premium must be greater than 0 if provided.');
        }

        return true;
    }
}
