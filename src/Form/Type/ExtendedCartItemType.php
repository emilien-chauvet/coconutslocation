<?php

namespace App\Form\Type;

use Sylius\Bundle\OrderBundle\Form\Type\CartItemType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

final class ExtendedCartItemType extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('startReservationDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début de location',
                //'attr' => [
                //    'readonly' => true,
                //],
            ])
            ->add('endReservationDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin de location',
                //'attr' => [
                //    'readonly' => true,
                //],
            ])
        ;
    }
    public static function getExtendedTypes(): iterable
    {
        return [CartItemType::class];
    }
}
