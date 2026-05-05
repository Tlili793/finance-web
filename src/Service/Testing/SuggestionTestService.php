<?php

namespace App\Service\Testing;

use App\Entity\Suggestion;
use App\Entity\User;

class SuggestionTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        \App\Entity\Profile $profile,
        string $title = 'New Feature Idea',
        string $script = 'It would be great to have a dark mode in the dashboard.'
    ): Suggestion {
        $suggestion = new Suggestion();
        $suggestion->setUser($user);
        $suggestion->setProfile($profile);
        $suggestion->setTitle($title);
        $suggestion->setScript($script);
        
        $this->persist($suggestion);
        return $suggestion;
    }
}
