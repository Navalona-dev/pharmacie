<?php

namespace App\Form;

use App\Entity\Revenu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\FrenchToDateTimeTransformer;

class RevenuType extends AbstractType
{
    private $frenchTransformer ;

    public function __construct(FrenchToDateTimeTransformer $frenchTransformer) {
        $this->frenchTransformer = $frenchTransformer;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('designation', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md',
                    'autocomplete' => 'off'
                ],
                'required' => true
            ])
            ->add('dateRevenu', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md',
                    'autocomplete' => 'off'
                ],
                'required' => true
            ])
        ;
        $builder->get('dateRevenu')->addModelTransformer($this->frenchTransformer);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Revenu::class,
        ]);
    }
}
