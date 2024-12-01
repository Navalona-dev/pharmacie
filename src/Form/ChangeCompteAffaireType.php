<?php

namespace App\Form;

use App\Entity\Compte;
use App\Entity\Affaire;
use App\Entity\Product;
use App\Entity\Application;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeCompteAffaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('compte', EntityType::class, [
                'class' => Compte::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('c')
                            ->where('c.genre = :genre')
                            ->andWhere('c.application = :application')
                            ->setParameter('genre', 1)
                            ->setParameter('application', $options['application']);
                },
                'choice_label' => function ($compte) {
                    $nom = $compte->getNom();
                    $telephone = $compte->getTelephone();

                    return $telephone ? sprintf('%s (%s)', $nom, $telephone) : $nom;
                },
                'attr' => [
                    'class' => 'form-control form-control-md chosen-select'
                ]
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Affaire::class,
            'application' => null,
        ]);
    }
}
