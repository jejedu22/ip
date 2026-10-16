<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('recipient', EmailType::class, [
            'label' => 'Adresse e-mail destinataire des fiches',
            'help' => 'Toutes les fiches complétées par les professionnels seront envoyées à cette adresse.',
            'constraints' => [new Assert\NotBlank(), new Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT), new Assert\Length(max: 255)],
        ]);
    }
}
