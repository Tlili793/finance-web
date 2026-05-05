<?php

namespace App\Service\Manager;

use App\Entity\Suggestion;

/**
 * Business Rules for Suggestion:
 *  1. Title must not be blank.
 *  2. Script must not be blank.
 *  3. Suggestion must be linked to a user.
 */
class SuggestionManager
{
    public function validate(Suggestion $suggestion): bool
    {
        if (empty(trim($suggestion->getTitle() ?? ''))) {
            throw new \InvalidArgumentException('Suggestion title must not be blank.');
        }

        if (empty(trim($suggestion->getScript() ?? ''))) {
            throw new \InvalidArgumentException('Suggestion script must not be blank.');
        }

        if ($suggestion->getUser() === null) {
            throw new \InvalidArgumentException('Suggestion must be linked to a user.');
        }

        return true;
    }
}
