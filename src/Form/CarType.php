<?php

namespace PrestaShop\Module\CarsManagement\Form;

use PrestaShop\Module\CarsManagement\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('registrationNumber', TextType::class, [
                'label' => 'Registration number',
                'help' => 'Leave empty for a new import without plates. Spaces and dashes are removed, Cyrillic letters are converted to Latin.',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 20, 'maxMessage' => 'Registration number cannot be longer than {{ limit }} characters.']),
                ],
            ])
            ->add('vin', TextType::class, [
                'empty_data' => '',
                'label' => 'VIN (chassis number)',
                'help' => '17 characters: letters and digits, without I, O and Q.',
                'constraints' => [
                    new NotBlank(['message' => 'VIN cannot be blank.']),
                    new Regex([
                        'pattern' => '/^[A-HJ-NPR-Z0-9]{17}$/i',
                        'message' => 'VIN must be exactly 17 letters and digits (I, O and Q are not allowed).',
                    ]),
                ],
            ])
            ->add('brand', TextType::class, [
                'empty_data' => '',
                'label' => 'Brand',
                'constraints' => [
                    new NotBlank(['message' => 'Brand cannot be blank.']),
                    new Length(['max' => 64, 'maxMessage' => 'Brand cannot be longer than {{ limit }} characters.']),
                ],
            ])
            ->add('model', TextType::class, [
                'empty_data' => '',
                'label' => 'Model',
                'constraints' => [
                    new NotBlank(['message' => 'Model cannot be blank.']),
                    new Length(['max' => 64, 'maxMessage' => 'Model cannot be longer than {{ limit }} characters.']),
                ],
            ])
            ->add('purchasePrice', NumberType::class, [
                'label' => 'Purchase price (EUR)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
                'invalid_message' => 'Purchase price must be a number.',
                'constraints' => [
                    new NotBlank(['message' => 'Purchase price cannot be blank.']),
                    new Positive(['message' => 'Purchase price must be greater than 0.']),
                ],
            ])
            ->add('salePrice', NumberType::class, [
                'label' => 'Sale price (EUR)',
                'help' => 'Leave empty while the car is not sold.',
                'required' => false,
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
                'invalid_message' => 'Sale price must be a number.',
                'constraints' => [
                    new Positive(['message' => 'Sale price must be greater than 0.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
            'translation_domain' => 'Modules.Carsmanagement.Admin',
        ]);
    }
}
