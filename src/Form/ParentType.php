<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ligne « parent / responsable légal » (rubrique 5).
 */
class ParentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $text = fn (string $label, int $max = 150): array => [
            'label' => $label,
            'required' => false,
            'constraints' => [new Assert\Length(max: $max)],
        ];

        $builder
            ->add('nom', TextType::class, $text('Nom (et nom de jeune fille)'))
            ->add('prenom', TextType::class, $text('Prénom'))
            ->add('naissance', TextType::class, $text('Date et lieu de naissance'))
            ->add('adresse', TextType::class, $text('Adresse (si différente de celle du ou des mineurs concernés)', 255))
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'constraints' => [new Assert\Regex('/^[0-9 .+()\-]{6,25}$/', message: 'Numéro de téléphone invalide.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['label' => false]);
    }
}
