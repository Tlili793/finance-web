<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Trait\BlameableTrait;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loan')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Loan
{
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $borrower = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    private ?string $interestRate = null;

    #[ORM\Embedded(class: DateRange::class, columnPrefix: false)]
    #[Assert\Valid]
    private DateRange $dateRange;

    #[ORM\Column(length: 20, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    /** @var Collection<int, Repayment> */
    #[ORM\OneToMany(mappedBy: 'loan', targetEntity: Repayment::class)]
    private Collection $repayments;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updateAuditTimestampsOnPersist();
    }

    public function __construct()
    {
        $this->dateRange = new DateRange();
        $this->repayments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->borrower; }
    public function setUser(?User $user): static { $this->borrower = $user; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getInterestRate(): ?string { return $this->interestRate; }
    public function setInterestRate(string $interestRate): static { $this->interestRate = $interestRate; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->dateRange->getStartDate(); }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->dateRange = new DateRange($startDate, $this->dateRange->getEndDate()); return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->dateRange->getEndDate(); }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->dateRange = new DateRange($this->dateRange->getStartDate(), $endDate); return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    protected function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    /** @return Collection<int, Repayment> */
    public function getRepayments(): Collection { return $this->repayments; }
}