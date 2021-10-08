<?php

namespace App\Form;

use App\Entity\SmartDevice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmartDeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('specification')
            ->add('moduleId')
            ->add('slug')
            ->add('programming')
            ->add('createdAt')
            ->add('editedAt')
            ->add('type')
            ->add('cleverBox')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmartDevice::class,
        ]);
    }
}
