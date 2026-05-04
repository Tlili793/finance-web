<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Ignore;
use App\Entity\Email;
use App\Entity\Phone;
use Symfony\Component\Uid\Uuid;
use SensitiveParameter;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ["groups" => ["user:read"]],
    denormalizationContext: ["groups" => ["user:write"]],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(["user:read"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Regex(pattern: '/^[a-zA-Z\s]+$/', message: 'Name can only contain letters and spaces.')]
    private ?string $name = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Valid]
    private Email $email;

    #[ORM\Column(length: 255)]
    #[Ignore]
    private ?string $passwordHash = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(["user:read", "user:write"])]
    private ?Role $role = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?self $createdBy = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $updatedBy = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private bool $isVerified = false;

    #[ORM\Embedded(class: Phone::class, columnPrefix: false)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Valid]
    private ?Phone $phone = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["user:write"])]
    private ?string $verificationCode = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private ?bool $googleAccount = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private bool $faceRegistered = false;
    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 1])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Budget::class)]
    private Collection $budgets;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Complaint::class)]
    private Collection $complaints;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InsuredAsset::class)]
    private Collection $insuredAssets;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Loan::class)]
    private Collection $loans;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Transaction::class)]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ContractRequest::class)]
    private Collection $contractRequests;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdBy = $this;
        $this->email = new Email();
        $this->phone = new Phone();
        $this->budgets = new ArrayCollection();
        $this->complaints = new ArrayCollection();
        $this->insuredAssets = new ArrayCollection();
        $this->loans = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->contractRequests = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email->getAddress();
    }

    public function setEmail(string $email): static
    {
        $this->email = new Email($email);
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email->getAddress();
    }

    public function getRoles(): array
    {
        $roles = ["ROLE_USER"];

        if ($this->role && str_contains(strtoupper($this->role->getRoleName()), 'ADMIN')) {
            $roles[] = "ROLE_ADMIN";
        }

        return array_unique($roles);
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;
        return $this;
    }

    // For backward compatibility or convenience
    public function getRoleId(): ?int
    {
        return $this->role?->getId();
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function setPassword(#[SensitiveParameter] string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function setPasswordHash(#[SensitiveParameter] string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    protected function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    protected function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?self
    {
        return $this->createdBy;
    }

    protected function setCreatedBy(?self $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?self
    {
        return $this->updatedBy;
    }

    protected function setUpdatedBy(?self $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone?->getNumber();
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone ? new Phone($phone) : null;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): static
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getGoogleAccount(): ?bool
    {
        return $this->googleAccount;
    }

    public function setGoogleAccount(?bool $googleAccount): static
    {
        $this->googleAccount = $googleAccount;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    protected function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function recordLogin(): void
    {
        $this->lastLogin = new \DateTime();
    }

    public function isFaceRegistered(): bool
    {
        return $this->faceRegistered;
    }

    public function setFaceRegistered(bool $faceRegistered): static
    {
        $this->faceRegistered = $faceRegistered;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getBudgets(): Collection { return $this->budgets; }
    public function getComplaints(): Collection { return $this->complaints; }
    public function getInsuredAssets(): Collection { return $this->insuredAssets; }
    public function getLoans(): Collection { return $this->loans; }
    public function getTransactions(): Collection { return $this->transactions; }
    public function getContractRequests(): Collection { return $this->contractRequests; }
}

