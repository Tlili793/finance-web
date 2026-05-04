<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\InsuredContractStatus;
use App\Repository\InsuredContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Trait\BlameableTrait;

#[ORM\Entity(repositoryClass: InsuredContractRepository::class)]
#[ORM\Table(name: 'insured_contract')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class InsuredContract
{
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $assetRef = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $boldsignDocumentId = null;

    #[ORM\Column(length: 20, options: ['default' => 'NOT_SIGNED'])]
    private string $status = 'NOT_SIGNED';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $localFilePath = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updateAuditTimestampsOnPersist();
    }

    public function getId(): ?int { return $this->id; }

    public function getAssetRef(): ?string { return $this->assetRef; }
    public function setAssetRef(string $assetRef): static { $this->assetRef = $assetRef; return $this; }

    public function getBoldsignDocumentId(): ?string { return $this->boldsignDocumentId; }
    public function setBoldsignDocumentId(string $boldsignDocumentId): static { $this->boldsignDocumentId = $boldsignDocumentId; return $this; }

    public function getStatus(): InsuredContractStatus { return InsuredContractStatus::from($this->status); }
    public function setStatus(InsuredContractStatus $status): static { $this->status = $status->value; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    protected function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getSignedAt(): ?\DateTimeInterface { return $this->signedAt; }
    protected function setSignedAt(?\DateTimeInterface $signedAt): static { $this->signedAt = $signedAt; return $this; }

    public function getLocalFilePath(): ?string { return $this->localFilePath; }
    public function setLocalFilePath(?string $localFilePath): static { $this->localFilePath = $localFilePath; return $this; }
}
