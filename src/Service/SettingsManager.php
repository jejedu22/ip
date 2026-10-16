<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SettingsManager
{
    public const RECIPIENT = 'recipient_email';

    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%env(DEFAULT_RECIPIENT)%')]
        private readonly string $defaultRecipient,
    ) {
    }

    /** Adresse à laquelle les fiches sont envoyées. */
    public function getRecipient(): string
    {
        return $this->em->find(Setting::class, self::RECIPIENT)?->getValue() ?? $this->defaultRecipient;
    }

    public function setRecipient(string $email): void
    {
        $setting = $this->em->find(Setting::class, self::RECIPIENT);
        if ($setting) {
            $setting->setValue($email);
        } else {
            $this->em->persist(new Setting(self::RECIPIENT, $email));
        }
        $this->em->flush();
    }
}
