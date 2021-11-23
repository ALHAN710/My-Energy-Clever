<?php

namespace App\Form;

use App\Entity\Enterprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnterpriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('socialReason')
            ->add('niu')
            ->add('rccm')
            ->add('address')
            ->add('email')
            ->add('phoneNumber')
            ->add('createdAt')
            ->add('logo')
            ->add('country')
            ->add('editedAt')
            ->add('isActive')
            ->add('slug')
            ->add('accountType')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Enterprise::class,
        ]);
    }
}
