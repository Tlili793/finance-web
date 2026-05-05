<?php

namespace App\Tests\Service;

use App\Entity\Suggestion;
use App\Entity\User;
use App\Service\Manager\SuggestionManager;
use PHPUnit\Framework\TestCase;

class SuggestionManagerTest extends TestCase
{
    private SuggestionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SuggestionManager();
    }

    private function validSuggestion(): Suggestion
    {
        $user = $this->createMock(User::class);
        $suggestion = new Suggestion();
        $suggestion->setTitle('Better UI');
        $suggestion->setScript('We need a better dashboard interface.');
        $suggestion->setUser($user);
        return $suggestion;
    }

    public function testValidSuggestionReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validSuggestion()));
    }

    public function testBlankTitleThrows(): void
    {
        $suggestion = $this->validSuggestion();
        $suggestion->setTitle('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/title must not be blank/i');
        $this->manager->validate($suggestion);
    }

    public function testBlankScriptThrows(): void
    {
        $suggestion = $this->validSuggestion();
        $suggestion->setScript('   ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/script must not be blank/i');
        $this->manager->validate($suggestion);
    }

    public function testMissingUserThrows(): void
    {
        $suggestion = $this->validSuggestion();
        $suggestion->setUser(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a user/i');
        $this->manager->validate($suggestion);
    }
}
