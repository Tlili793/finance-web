<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Money
{
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(["transaction:read", "transaction:write"])]
    private ?string $amount = null;

    #[ORM\Column(length: 10, options: ['default' => 'TND'])]
    #[Groups(["transaction:read", "transaction:write"])]
    private ?string $currency = 'TND';

    public function __construct(?string $amount = null, ?string $currency = 'TND')
    {
        $this->amount = $amount;
        $this->currency = $currency ?: 'TND';
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function __toString(): string
    {
        return $this->amount . ' ' . $this->currency;
    }
}
