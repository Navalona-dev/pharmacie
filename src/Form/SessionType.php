<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Session;
use App\Entity\User;
use App\Service\ApplicationManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    private $application;
    public function __construct(ApplicationManager $applicationManager)
    {
        $this->application = $applicationManager->getApplicationActive();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $application = $this->application;

        $builder
            ->remove('dateDebut', null, [
                'widget' => 'single_text',
            ])
            ->remove('dateFin', null, [
                'widget' => 'single_text',
            ])
            ->remove('heureDebut', null, [
                'widget' => 'single_text',
            ])
            ->remove('heureFin', null, [
                'widget' => 'single_text',
            ])
            ->remove('nom')
            ->remove('description')
            ->remove('isActive')
            ->add('users', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ($application)  {
                    return $er->createQueryBuilder('u')
                    ->andWhere('u.appActive = :application')
                    ->setParameter('application', $application)
                    ->orderBy('u.prenom');
                },
               // 'choice_label' => 'prenom',
                'multiple' => true,
                'required' => false,
                'placeholder' => 'Choisir point de vente',
                'attr'=> ['class' => 'form-control chosen-select']
            ])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}
