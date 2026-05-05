<?php

namespace App\Form;

use App\Entity\Society;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocietyRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la societe',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom de votre entreprise'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email professionnel'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Mot de passe (min 4 caractères)'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirmez le mot de passe'],
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telephone',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse de siege social'],
            ])
            ->add('domain', TextType::class, [
                'label' => 'Domaine d\'activite',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Technologie, Finance, Sante'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de l\'entreprise',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Decrivez votre entreprise en quelques lignes',
                    'rows' => 4,
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://www.example.com'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Society::class,
        ]);
    }
}
