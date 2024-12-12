<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Compte;
use App\Entity\ProduitCategorie;
use App\Form\DatePeremptionType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $application = $options['application'];

        $builder
            ->add('compte', EntityType::class, [
                'class' => Compte::class,
                'choice_label' => 'nom',
                'query_builder' => function (EntityRepository $er) use ($application) {
                    return $er->createQueryBuilder('c')
                        ->where('c.application = :application')
                        ->andWhere('c.genre = :genre')
                        ->setParameter('application', $application)
                        ->setParameter('genre', 2);
                },
                'attr' => [
                    'class' => 'form-control form-control-md chosen-select'
                ],
                'placeholder' => "Selectionner un fournisseur"
            ])
            ->add('qtt', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md mb-3',
                    'autocomplete' => 'off'
                ],
                'required' => true
            ])
            ->add('datePeremption', DatePeremptionType::class, [
               
            ]);
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
            'application' => null,
        ]);
    }
}
