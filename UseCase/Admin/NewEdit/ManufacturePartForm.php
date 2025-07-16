<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Manufacture\Part\UseCase\Admin\NewEdit;


use BaksDev\Delivery\Forms\Delivery\DeliveryForm;
use BaksDev\Manufacture\Part\Entity\Depends\ManufacturePartDepends;
use BaksDev\Manufacture\Part\Entity\ManufacturePart;
use BaksDev\Manufacture\Part\Repository\AllManufacturePart\AllManufacturePartInterface;
use BaksDev\Manufacture\Part\Repository\ManufacturePartChoice\ManufacturePartChoiceInterface;
use BaksDev\Manufacture\Part\Repository\ManufacturePartChoice\ManufacturePartChoiceResult;
use BaksDev\Manufacture\Part\Type\Id\ManufacturePartUid;
use BaksDev\Manufacture\Part\UseCase\Admin\ManufacturePart\ManufacturePartEntityForm;
use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Users\UsersTable\Repository\Actions\UsersTableActionsChoice\UsersTableActionsChoiceInterface;
use BaksDev\Users\UsersTable\Type\Actions\Event\UsersTableActionsEventUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ManufacturePartForm extends AbstractType
{
    public function __construct(
        private readonly UsersTableActionsChoiceInterface $usersTableActionsChoice,
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly ManufacturePartChoiceInterface $manufacturePartChoice,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Категория производства
         */

        $builder
            ->add('category', ChoiceType::class, [
                'choices' => $this->categoryChoice->findAll(),
                'choice_value' => function(?CategoryProductUid $category) {
                    return $category?->getValue();
                },
                'choice_label' => function(CategoryProductUid $category) {
                    return (is_int($category->getAttr()) ? str_repeat(' - ', $category->getAttr() - 1) : '').$category->getOptions();
                },

                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => false,
            ]);


        $builder->get('category')->addModelTransformer(
            new CallbackTransformer(
                function($category) {
                    return $category instanceof CategoryProductUid ? $category->getValue() : $category;
                },
                function($category) {
                    return $category instanceof CategoryProductUid ? $category : new CategoryProductUid($category);
                }
            )
        );

        $formModifier = function(FormInterface $form, ?CategoryProductUid $category = null): void {

            /** @var ManufacturePartDTO $ManufacturePartDTO */
            //$ManufacturePartDTO = $form->getData();


            $choice = $this->usersTableActionsChoice
                ->forCategory($category)
                ->getCollection();


            $form
                ->add('action', ChoiceType::class, [
                    'choices' => $choice,
                    'choice_value' => function(?UsersTableActionsEventUid $action) {
                        return $action?->getValue();
                    },
                    'choice_label' => function(UsersTableActionsEventUid $action) {

                        return $action->getAttr();
                    },

                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ]);

        };


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formModifier): void {

            /** @var ManufacturePartDTO $ManufacturePartDTO */
            $ManufacturePartDTO = $event->getData();


            if($ManufacturePartDTO->getFixed())
            {

                $formModifier($event->getForm(), $ManufacturePartDTO->getCategory());

            }
        });


        $builder->get('category')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) use ($formModifier): void {
                $data = $event->getData();
                $formModifier($event->getForm()->getParent(), $data ? new CategoryProductUid($data) : null);
            }
        );


        $builder->add('complete', DeliveryForm::class, ['required' => false]);

        $builder->add('comment', TextareaType::class, ['required' => false]);

        /* Сохранить ******************************************************/
        $builder->add(
            'manufacture_part',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ManufacturePartDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}