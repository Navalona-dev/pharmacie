<?php

namespace App\Form;

use App\Entity\Affaire;
use App\Entity\Product;
use App\Entity\Application;
use App\Entity\ProduitCategorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('qtt', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md',
                    'autocomplete' => 'off',
                    'readonly' => true
                ],
                'required' => true
            ])
            ->add('typeVente', HiddenType::class, [
                'attr' => [
                    'class' => 'form-control from-control-md',
                ],
                'required' => true
            ])
            /*->add('typeVente', ChoiceType::class, [
                'choices'  => [
                    'Gros' => 'gros',
                    'Detail' => 'detail',
                ],
                'attr' => [
                    'class' => 'form-control form-control-md',
                ],
                'placeholder' => 'Selectionner un type',
                'required' => true
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
