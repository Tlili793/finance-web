<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BudgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
#[ORM\Table(name: 'budget')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
#[Assert\NotBlank(message: 'Budget name is required')]
#[Assert\Length(min: 3, max: 150, minMessage: 'Name must be at least 3 characters')]
private ?string $name = null;

#[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
#[Assert\NotBlank(message: 'Amount is required')]
#[Assert\Positive(message: 'Amount must be greater than 0')]
private ?string $amount = null;

    #[ORM\Embedded(class: DateRange::class, columnPrefix: false)]
    #[Assert\Valid]
    private DateRange $dateRange;
    

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'budgets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00'])]
    private ?string $spentAmount = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: Bill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bills;

    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: Expense::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $expenses;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function __construct()
    {
        $this->dateRange = new DateRange();
        $this->bills = new ArrayCollection();
        $this->expenses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->dateRange->getStartDate(); }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->dateRange = new DateRange($startDate, $this->dateRange->getEndDate()); return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->dateRange->getEndDate(); }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->dateRange = new DateRange($this->dateRange->getStartDate(), $endDate); return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getSpentAmount(): ?string { return $this->spentAmount; }
    public function setSpentAmount(string $spentAmount): static { $this->spentAmount = $spentAmount; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    protected function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getBills(): Collection { return $this->bills; }
    public function getExpenses(): Collection { return $this->expenses; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    protected function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    protected function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getUpdatedBy(): ?User { return $this->updatedBy; }
    protected function setUpdatedBy(?User $updatedBy): static { $this->updatedBy = $updatedBy; return $this; }
    
    public function getStatus(): string
    {
        if ((float)$this->amount == 0) return 'No Budget';
        $percentage = ((float)$this->spentAmount / (float)$this->amount) * 100;
        if ($percentage <= 50) return 'On Track';
        if ($percentage <= 75) return 'Warning';
        if ($percentage <= 90) return 'Near Limit';
        return 'Overspent';
    }
}