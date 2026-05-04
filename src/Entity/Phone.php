<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Phone
{
    #[ORM\Column(name: 'phone', length: 30, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-]+$/', message: 'Invalid phone number format.')]
    private ?string $number = null;

    public function __construct(?string $number = null)
    {
        $this->number = $number;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
