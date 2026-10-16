<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * « Fiche de transmission d'une information préoccupante à l'usage des professionnels ».
 * Les données sont des tableaux : elles ne sont jamais persistées.
 */
class TransmissionType extends AbstractType
{
    public const MODES = [
        'Accueil' => 'Accueil',
        'Téléphone' => 'Téléphone',
        'Courrier (joindre copie)' => 'Courrier (joindre copie)',
        'Courriel (joindre copie)' => 'Courriel (joindre copie)',
    ];

    public const QUALITES = [
        'Père' => 'Père',
        'Mère' => 'Mère',
        'Enfant lui même' => 'Enfant lui même',
        'Famille' => 'Famille',
        'Autre enfant' => 'Autre enfant',
        'Ami de la famille, voisin, proche' => 'Ami de la famille, voisin, proche',
        'Personne anonyme' => 'Personne anonyme',
        'PMI' => 'PMI',
        'Autres services sociaux (hors CD), associations' => 'Autres services sociaux (hors CD), associations',
        'Éducation Nationale, établissements privés' => 'Éducation Nationale, établissements privés',
        'Établissements relevant de la protection de l’enfance et médico-sociaux' => 'Établissements relevant de la protection de l’enfance et médico-sociaux',
        'Hôpital' => 'Hôpital',
        'Police, Gendarmerie' => 'Police, Gendarmerie',
        'Mairie' => 'Mairie',
        'Autres' => 'Autres',
    ];

    public const AUTORITE = [
        'Parent 1' => 'Parent 1',
        'Parent 2' => 'Parent 2',
        'Administration' => 'Administration',
        'Autres' => 'Autres',
    ];

    public const RESIDENCES = [
        'Avec les parents' => 'Avec les parents',
        'Garde alternée' => 'Garde alternée',
        'Avec sa mère seule' => 'Avec sa mère seule',
        'Avec son père seul' => 'Avec son père seul',
        'Avec sa mère dans une famille recomposée' => 'Avec sa mère dans une famille recomposée',
        'Avec son père dans une famille recomposée' => 'Avec son père dans une famille recomposée',
        'Avec un autre membre de la famille' => 'Avec un autre membre de la famille',
        'Chez un tiers digne de confiance' => 'Chez un tiers digne de confiance',
        'En famille d’accueil' => 'En famille d’accueil',
        'En établissement' => 'En établissement',
        'Autres' => 'Autres',
    ];

    public const OUI_NON_NSP = ['Oui' => 'Oui', 'Non' => 'Non', 'Ne sait pas' => 'Ne sait pas'];

    public const INFORMES = [
        'Parent 1' => 'Parent 1',
        'Parent 2' => 'Parent 2',
        'Autre détenteur de l’autorité parentale' => 'Autre détenteur de l’autorité parentale',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $text = fn (string $label, bool $required = false, int $max = 150): array => [
            'label' => $label,
            'required' => $required,
            'constraints' => array_filter([$required ? new Assert\NotBlank() : null, new Assert\Length(max: $max)]),
        ];
        $phone = fn (string $label): array => [
            'label' => $label,
            'required' => false,
            'constraints' => [new Assert\Regex('/^[0-9 .+()\-]{6,25}$/', message: 'Numéro de téléphone invalide.')],
        ];
        $group = fn (string $name): FormBuilderInterface => $builder->create($name, FormType::class, ['label' => false]);

        // 1 - Recueil des premiers éléments
        $recueil = $group('recueil')
            ->add('date', DateType::class, [
                'label' => 'Date de recueil',
                'widget' => 'single_text',
                'constraints' => [new Assert\NotBlank(), new Assert\LessThanOrEqual('today', message: 'La date de recueil ne peut pas être dans le futur.')],
            ])
            ->add('heure', TimeType::class, [
                'label' => 'Heure',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode de recueil',
                'choices' => self::MODES,
                'expanded' => true,
                'constraints' => [new Assert\NotBlank()],
            ]);
        $builder->add($recueil);

        // 2 - Service / partenaire qui transmet
        $service = $group('service')
            ->add('nom', TextType::class, $text('Nom', true))
            ->add('prenom', TextType::class, $text('Prénom', true))
            ->add('fonction', TextType::class, $text('Fonction', true))
            ->add('organisme', TextareaType::class, $text('Organisme / Service / Adresse', true, 500))
            ->add('fixe', TextType::class, $phone('Téléphone fixe'))
            ->add('mobile', TextType::class, $phone('Téléphone mobile'))
            ->add('courriel', EmailType::class, [
                'label' => 'Courriel',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            ]);
        $builder->add($service);

        // 3 - Informateur
        $informateur = $group('informateur')
            ->add('anonyme', CheckboxType::class, [
                'label' => 'L’anonymat est sollicité : ne pas renseigner l’identification de la personne',
                'required' => false,
            ])
            ->add('nom', TextType::class, $text('Nom'))
            ->add('prenom', TextType::class, $text('Prénom'))
            ->add('organisme', TextType::class, $text('Organisme ou service le cas échéant'))
            ->add('adresse', TextareaType::class, $text('Adresse professionnelle', false, 500))
            ->add('fixe', TextType::class, $phone('Téléphone fixe'))
            ->add('mobile', TextType::class, $phone('Téléphone mobile'))
            ->add('courriel', EmailType::class, [
                'label' => 'Courriel',
                'required' => false,
                'constraints' => [new Assert\Email(), new Assert\Length(max: 255)],
            ])
            ->add('qualite', ChoiceType::class, [
                'label' => 'Qualité de l’informateur',
                'choices' => self::QUALITES,
                'expanded' => true,
                'required' => false,
            ])
            ->add('qualite_autre', TextType::class, $text('Précisez (« Autres »)'));
        $builder->add($informateur);

        // 4 - Mineurs concernés
        $builder
            ->add('mineurs', CollectionType::class, [
                'label' => false,
                'entry_type' => MineurType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => false,
                'prototype_name' => '__index__',
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Indiquez au moins un mineur concerné.', max: 15)],
            ])
            ->add('adresse_mineurs', TextType::class, $text('Adresse', true, 255))
            ->add('code_postal', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{5}$/', message: 'Code postal invalide (5 chiffres).')],
            ])
            ->add('commune', TextType::class, $text('Commune', true, 100))
            ->add('autres_enfants', CollectionType::class, [
                'label' => false,
                'entry_type' => MineurType::class,
                'entry_options' => ['with_address' => true],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => false,
                'prototype_name' => '__index__',
                'constraints' => [new Assert\Count(max: 15)],
            ]);

        // 5 - Parents ou responsables légaux
        $parents = $group('parents')
            ->add('parent1', ParentType::class)
            ->add('parent2', ParentType::class)
            ->add('autre', ParentType::class);
        $builder->add($parents);
        $builder
            ->add('autorite', ChoiceType::class, [
                'label' => 'Autorité parentale',
                'choices' => self::AUTORITE,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('autorite_precision', TextType::class, $text('Précisez (« Administration » / « Autres »)'))
            ->add('residence', ChoiceType::class, [
                'label' => 'Résidence de l’enfant',
                'choices' => self::RESIDENCES,
                'expanded' => true,
                'required' => false,
            ])
            ->add('residence_autre', TextType::class, $text('Précisez (« Autres »)'))
            ->add('suivi_social', ChoiceType::class, [
                'label' => 'Existe-t-il un suivi social ?',
                'choices' => self::OUI_NON_NSP,
                'expanded' => true,
                'required' => false,
            ])
            ->add('suivi_social_coordonnees', TextareaType::class, $text('Coordonnées du suivi', false, 500))
            ->add('suivi_educatif', ChoiceType::class, [
                'label' => 'Existe-t-il un suivi éducatif ?',
                'choices' => self::OUI_NON_NSP,
                'expanded' => true,
                'required' => false,
            ])
            ->add('suivi_educatif_coordonnees', TextareaType::class, $text('Coordonnées du suivi', false, 500));

        // 6 - Éléments préoccupants
        $builder->add('elements', TextareaType::class, [
            'label' => 'Décrire, dater et contextualiser les faits, les comportements observés et propos tenus',
            'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 20000)],
        ]);

        // 7 - Information aux parents
        $builder
            ->add('parents_informes', ChoiceType::class, [
                'label' => 'Les parents ou responsables légaux ont-ils été informés de la transmission des éléments d’inquiétudes, de dangers ou de risques de danger concernant leur(s) enfant(s) à la CRIP ?',
                'choices' => ['Oui' => 'Oui', 'Non' => 'Non'],
                'expanded' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('parents_informes_qui', ChoiceType::class, [
                'label' => 'Qui ?',
                'choices' => self::INFORMES,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('parents_informes_non', TextareaType::class, $text('Préciser pourquoi', false, 2000));

        // 8 - Éléments complémentaires
        $builder->add('complements', TextareaType::class, [
            'label' => 'Éléments complémentaires à rapporter sur la situation et éléments antérieurs',
            'required' => false,
            'constraints' => [new Assert\Length(max: 20000)],
        ]);

        // Pièces jointes (copie du courrier / courriel). Transmises par e-mail, jamais conservées.
        $builder->add('pieces_jointes', FileType::class, [
            'label' => 'Pièces jointes (copie du courrier ou du courriel)',
            'multiple' => true,
            'required' => false,
            'constraints' => [
                new Assert\Count(max: 5, maxMessage: '5 fichiers maximum.'),
                new Assert\All([new Assert\File(
                    maxSize: '5M',
                    mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'],
                    mimeTypesMessage: 'Formats acceptés : PDF, JPG, PNG.',
                )]),
            ],
        ]);

        // Champ piège anti-robots (doit rester vide)
        $builder->add('website', TextType::class, ['label' => 'Ne pas remplir', 'required' => false]);
    }
}
