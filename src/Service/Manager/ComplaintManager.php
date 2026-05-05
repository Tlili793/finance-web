<?php

namespace App\Service\Manager;

use App\Entity\Complaint;

/**
 * Business Rules for Complaint:
 *  1. Subject must not be blank.
 *  2. Subject must not contain profanity (basic keyword check).
 *  3. Complaint date must not be null.
 *  4. Status must be one of: PENDING, IN_REVIEW, RESOLVED, REJECTED.
 */
class ComplaintManager
{
    private const VALID_STATUSES = ['PENDING', 'IN_REVIEW', 'RESOLVED', 'REJECTED'];
    private const PROFANITY_LIST = ['badword', 'offensive', 'slur']; // simplified list

    public function validate(Complaint $complaint): bool
    {
        $subject = trim($complaint->getSubject() ?? '');
        if (empty($subject)) {
            throw new \InvalidArgumentException('Complaint subject must not be blank.');
        }

        foreach (self::PROFANITY_LIST as $word) {
            if (stripos($subject, $word) !== false) {
                throw new \InvalidArgumentException('Complaint subject contains inappropriate language.');
            }
        }

        if ($complaint->getComplaintDate() === null) {
            throw new \InvalidArgumentException('Complaint date is required.');
        }

        if (!in_array($complaint->getStatus(), self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Status must be one of: %s.', implode(', ', self::VALID_STATUSES))
            );
        }

        return true;
    }
}
