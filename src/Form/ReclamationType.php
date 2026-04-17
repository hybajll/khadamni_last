<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Enum\TypeReclamation; // Assure-toi que cet Enum existe
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujet', TextType::class, [
                'label' => 'Sujet',
                'attr' => ['placeholder' => 'Ex: Problème de connexion']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5]
            ])
            ->add('type', EnumType::class, [
                'class' => TypeReclamation::class,
                'label' => 'Type de réclamation',
                'choice_label' => fn ($choice) => $choice->value, // Ou $choice->getLabel() si tu as la méthode
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class, // Indispensable pour lier le formulaire à l'entité
        ]);
    }
}