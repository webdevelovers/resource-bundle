<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebDevelovers\ResourceBundle\Security\DoctrineUserClassResolver;
use WebDevelovers\ResourceBundle\Toolbox\Entity\ActivityType;
use WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Model\PlanActivity;

class PlanActivityType extends AbstractType
{
    public function __construct(
        private readonly DoctrineUserClassResolver $userClassResolver,
    ) {
    }

    /** @param array<string,mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userClass = $this->userClassResolver->resolve();

        $builder
            ->add('summary', TextType::class, ['label' => 'Nome'])
            ->add('activityType', EntityType::class, [
                'class' => ActivityType::class,
                //'autocomplete' => true,
                'required' => true,
                'label' => 'Tipo',
            ])
            ->add('dueDate', DateTimeType::class, [
                'html5' => true,
                'label' => 'Data di scadenza',
                'required' => true,
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => $userClass,
                //'autocomplete' => true,
                'required' => true,
                'label' => 'Responsabile',
                'query_builder' => static fn (EntityRepository $userRepository) => $userRepository
                    ->createQueryBuilder('user')
                    ->andWhere('user.enabled = :enabled')
                    ->setParameter('enabled', true)
                    ->orderBy('user.fullName', 'ASC'),
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label_attr' => ['class' => 'text-capitalize'],
                'label' => 'wd.field.description',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlanActivity::class,
        ]);
    }
}
