<?php

namespace App\Entity;

use App\Repository\SecretRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SecretRepository::class)]
class Secret
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $hash = null;

    #[ORM\Column(length: 255)]
    private ?string $secretText = null;

    #[ORM\Column(type: 'datetime_immutable_micro')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable_micro', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private ?int $remainingViews = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable("now");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = sha1($hash);

        return $this;
    }

    public function getSecretText(): ?string
    {
        return $this->secretText;
    }

    public function setSecretText(string $secretText): self
    {
        $this->secretText = $secretText;

        $this->setHash(random_bytes(5) . $this->secretText . random_bytes(5));
        
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(int $minutes): self
    {
        if ($minutes > 0)
        {
            $this->expiresAt = new \DateTimeImmutable('+' . $minutes . ' minutes');
        }

        return $this;
    }

    public function getRemainingViews(): ?int
    {
        return $this->remainingViews;
    }

    public function setRemainingViews(int $remainingViews): self
    {
        $this->remainingViews = $remainingViews;

        return $this;
    }

    public function minusRemainingViews(): self
    {
        $this->remainingViews--;

        return $this;
    }
}
