<?php

namespace App\Service\Manager;

use App\Entity\Profile;

/**
 * Business Rules for Profile:
 *  1. Description must be between 10 and 2000 characters.
 *  2. Profile must be linked to a user.
 */
class ProfileManager
{
    public function validate(Profile $profile): bool
    {
        $description = trim($profile->getDescription() ?? '');
        if (strlen($description) < 10 || strlen($description) > 2000) {
            throw new \InvalidArgumentException('Profile description must be between 10 and 2000 characters.');
        }

        if ($profile->getUser() === null) {
            throw new \InvalidArgumentException('Profile must be linked to a user.');
        }

        return true;
    }
}
