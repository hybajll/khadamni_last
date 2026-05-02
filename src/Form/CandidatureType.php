<?php

namespace App\Form;

use App\Entity\Candidature;
use App\Entity\Offer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['placeholder' => 'exemple@domaine.com'],
            ])
            ->add('cvPath', FileType::class, [
                'label' => 'Télécharger votre CV (PDF uniquement)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide.',
                    ]),
                ],
            ])
            ->add('offre', EntityType::class, [
                'class' => Offer::class,
                'choice_label' => 'title',
                'label' => 'Choisir une offre',
                'placeholder' => 'Sélectionnez une offre',
                'disabled' => (bool) ($options['offre_disabled'] ?? false),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
            'offre_disabled' => false,
        ]);
    }
}

