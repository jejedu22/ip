<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Paramètre applicatif clé/valeur (ex. adresse du destinataire des fiches).
 */
#[ORM\Entity]
#[ORM\Table(name: 'setting')]
class Setting
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'name', length: 64)]
        private string $name,
        #[ORM\Column(type: 'text')]
        private string $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
