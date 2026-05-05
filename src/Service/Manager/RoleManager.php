<?php

namespace App\Service\Manager;

use App\Entity\Role;

/**
 * Business Rules for Role:
 *  1. Role name must not be blank.
 */
class RoleManager
{
    public function validate(Role $role): bool
    {
        if (empty(trim($role->getRoleName() ?? ''))) {
            throw new \InvalidArgumentException('Role name must not be blank.');
        }

        return true;
    }
}
