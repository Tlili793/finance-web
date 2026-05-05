<?php

namespace App\Entity;

use App\Repository\SuggestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: SuggestionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Suggestion
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'suggestions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'suggestions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $script = null;

    #[ORM\Column]
    private bool $listened = false;

    #[ORM\Column]
    private bool $started = false;

    public function __construct()
    {
    }

    // Getters and setters
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getProfile(): ?Profile { return $this->profile; }
    public function setProfile(?Profile $profile): static { $this->profile = $profile; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    
    /** @return array<string, mixed>|null */
    public function getData(): ?array { return $this->data; }

    /** @param array<string, mixed>|null $data */
    public function setData(?array $data): static { $this->data = $data; return $this; }
    public function getScript(): ?string { return $this->script; }
    public function setScript(string $script): static { $this->script = $script; return $this; }
    public function isListened(): ?bool { return $this->listened; }
    public function setListened(bool $listened): static { $this->listened = $listened; return $this; }
    public function isStarted(): ?bool { return $this->started; }
    public function setStarted(bool $started): static { $this->started = $started; return $this; }
}