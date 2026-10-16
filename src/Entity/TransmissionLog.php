<?php

namespace App\Entity;

use App\Repository\TransmissionLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace minimale d'un envoi : uniquement l'expéditeur et la date.
 * Aucune donnée du formulaire (mineurs, parents, faits…) n'est conservée.
 */
#[ORM\Entity(repositoryClass: TransmissionLogRepository::class)]
#[ORM\Table(name: 'transmission_log')]
#[ORM\Index(columns: ['sent_at'])]
class TransmissionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 200)]
        private string $senderName,
        #[ORM\Column(length: 255)]
        private string $senderEmail,
        #[ORM\Column]
        private \DateTimeImmutable $sentAt = new \DateTimeImmutable(),
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
