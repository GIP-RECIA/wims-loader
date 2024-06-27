<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class NameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Importer',
                'attr' => ['class' => 'wims_button'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            // Désactivation de la protection csrf car il y'a plusieurs fois le même formulaire sur la page
            'csrf_protection' => false,
        ]);
    }
}
