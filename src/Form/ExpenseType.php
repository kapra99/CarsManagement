<?php

namespace PrestaShop\Module\CarsManagement\Form;

use PrestaShop\Module\CarsManagement\Entity\CarExpense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ExpenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('expenseDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'datetime',
                'invalid_message' => 'Date is not valid.',
                'constraints' => [new NotBlank(['message' => 'Date cannot be blank.'])],
            ])
            ->add('typeOfExpense', ChoiceType::class, [
                'label' => 'Type',
                // ChoiceType wants label => value, our constant is value => label
                'choices' => array_flip(CarExpense::TYPES),
                'invalid_message' => 'Please choose a type from the list.',
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Amount (EUR)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
                'invalid_message' => 'Amount must be a number.',
                'constraints' => [
                    new NotBlank(['message' => 'Amount cannot be blank.']),
                    new Positive(['message' => 'Amount must be greater than 0.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CarExpense::class,
            'translation_domain' => 'Modules.Carsmanagement.Admin',
        ]);
    }
}
