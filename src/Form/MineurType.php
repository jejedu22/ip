<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ligne « mineur » (rubrique 4) ou « autre enfant présent au domicile ».
 */
class MineurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
            ->add('naissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [new Assert\LessThanOrEqual('today')],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu de naissance',
                'required' => false,
                'constraints' => [new Assert\Length(max: 100)],
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'required' => false,
                'placeholder' => '—',
                'choices' => ['Fille' => 'Fille', 'Garçon' => 'Garçon'],
            ]);

        if ($options['with_address']) {
            $builder->add('adresse', TextType::class, [
                'label' => 'Adresse (si différente de celle du ou des mineurs concernés)',
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['with_address' => false, 'label' => false]);
    }
}
