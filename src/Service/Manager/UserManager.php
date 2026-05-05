<?php

namespace App\Service\Manager;

use App\Entity\User;

/**
 * Business Rules for User:
 *  1. Name must only contain letters and spaces.
 *  2. Name must not be blank.
 *  3. Email must be valid (contains @ and .).
 *  4. Password hash must not be blank.
 */
class UserManager
{
    public function validate(User $user): bool
    {
        $name = trim($user->getName() ?? '');
        if (empty($name)) {
            throw new \InvalidArgumentException('User name is required.');
        }

        if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
            throw new \InvalidArgumentException('User name can only contain letters and spaces.');
        }

        $email = $user->getEmail();
        if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        if (empty($user->getPassword())) {
            throw new \InvalidArgumentException('User must have a password.');
        }

        return true;
    }
}
