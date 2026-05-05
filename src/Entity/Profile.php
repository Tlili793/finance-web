<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Profile
{
    use BlameableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'profiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 2000)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = 'active';

    /** @var Collection<int, Suggestion> */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Suggestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $suggestions;

    public function __construct()
    {
        $this->suggestions = new ArrayCollection();
    }

    // Getters and setters
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    /** @return Collection<int, Suggestion> */
    public function getSuggestions(): Collection { return $this->suggestions; }
    public function addSuggestion(Suggestion $suggestion): static { if (!$this->suggestions->contains($suggestion)) { $this->suggestions->add($suggestion); $suggestion->setProfile($this); } return $this; }
    public function removeSuggestion(Suggestion $suggestion): static { $this->suggestions->removeElement($suggestion); return $this; }
}