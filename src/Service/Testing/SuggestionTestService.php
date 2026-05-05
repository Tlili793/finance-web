<?php

namespace App\Service\Testing;

use App\Entity\Suggestion;
use App\Entity\User;

class SuggestionTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'New Feature Idea',
        string $content = 'It would be great to have a dark mode in the dashboard.'
    ): Suggestion {
        $suggestion = new Suggestion();
        $suggestion->setUser($user);
        $suggestion->setTitle($title);
        $suggestion->setContent($content);
        
        $this->persist($suggestion);
        return $suggestion;
    }
}
