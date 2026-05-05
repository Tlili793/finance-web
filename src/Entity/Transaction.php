<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Trait\BlameableTrait;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'app_transaction')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Transaction
{
    use BlameableTrait;

    public function __construct()
    {
        $this->money = new Money();
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: false)]
    #[Assert\Valid]
    private Money $money;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $type = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    private string $status = 'PENDING';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $referenceId = null;


    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updateAuditTimestampsOnPersist();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAmount(): ?string { return $this->money->getAmount(); }
    public function setAmount(string $amount): static { $this->money = new Money($amount, $this->money->getCurrency()); return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    protected function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getReferenceType(): ?string { return $this->referenceType; }
    public function setReferenceType(?string $referenceType): static { $this->referenceType = $referenceType; return $this; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $referenceId): static { $this->referenceId = $referenceId; return $this; }

    public function getCurrency(): ?string { return $this->money->getCurrency(); }
    public function setCurrency(string $currency): static { $this->money = new Money($this->money->getAmount(), $currency); return $this; }
}
