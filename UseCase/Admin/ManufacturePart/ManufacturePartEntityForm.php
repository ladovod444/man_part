<?php

declare(strict_types=1);

namespace BaksDev\Manufacture\Part\UseCase\Admin\ManufacturePart;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ManufacturePartEntityForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //
        //
        $builder->add('id', TextType::class);
        $builder->add('event', TextType::class);

        /* Сохранить ******************************************************/
        $builder->add(
            'manufacture_part_entity',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ManufacturePartEntityDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}