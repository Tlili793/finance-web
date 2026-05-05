<?php

namespace App\Service\Manager;

use App\Entity\InsuredAsset;

/**
 * Business Rules for InsuredAsset:
 *  1. Reference must not be blank.
 *  2. Type must not be blank.
 *  3. Declared value must be positive.
 *  4. Manufacture date must not be null and must not be in the future.
 */
class InsuredAssetManager
{
    public function validate(InsuredAsset $asset): bool
    {
        if (empty(trim($asset->getReference() ?? ''))) {
            throw new \InvalidArgumentException('Asset reference must not be blank.');
        }

        if (empty(trim($asset->getType() ?? ''))) {
            throw new \InvalidArgumentException('Asset type must not be blank.');
        }

        $value = (float) ($asset->getDeclaredValue() ?? '0');
        if ($value <= 0) {
            throw new \InvalidArgumentException('Declared value must be greater than 0.');
        }

        $date = $asset->getManufactureDate();
        if ($date === null) {
            throw new \InvalidArgumentException('Manufacture date is required.');
        }

        if ($date > new \DateTime('today')) {
            throw new \InvalidArgumentException('Manufacture date cannot be in the future.');
        }

        return true;
    }
}
