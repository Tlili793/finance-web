<?php

namespace App\Service\Manager;

use App\Entity\InsurancePackage;

/**
 * Business Rules for InsurancePackage:
 *  1. Name must not be blank.
 *  2. Asset type must not be blank.
 *  3. Base price must be a positive number.
 *  4. Risk multiplier must be > 0.
 *  5. Duration in months must be a positive integer (≥ 1).
 */
class InsurancePackageManager
{
    public function validate(InsurancePackage $package): bool
    {
        if (empty(trim($package->getName() ?? ''))) {
            throw new \InvalidArgumentException('Package name must not be blank.');
        }

        if (empty(trim($package->getAssetType() ?? ''))) {
            throw new \InvalidArgumentException('Asset type must not be blank.');
        }

        $basePrice = (float) ($package->getBasePrice() ?? '0');
        if ($basePrice <= 0) {
            throw new \InvalidArgumentException('Base price must be greater than 0.');
        }

        $multiplier = (float) ($package->getRiskMultiplier() ?? '0');
        if ($multiplier <= 0) {
            throw new \InvalidArgumentException('Risk multiplier must be greater than 0.');
        }

        $duration = $package->getDurationMonths() ?? 0;
        if ($duration < 1) {
            throw new \InvalidArgumentException('Duration must be at least 1 month.');
        }

        return true;
    }
}
