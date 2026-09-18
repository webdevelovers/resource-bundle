<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Activity;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /** @return Activity[] */
    public function getActivities(
        Uuid $subject,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.subject = :subject')
            ->setParameter('subject', $subject, UuidType::NAME)
            ->addOrderBy('a.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /** @return Activity[] */
    public function getAssignedActivities(
        UserInterface $assignedTo,
        int|null $limit = 5,
        bool $done = false,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.assignedTo = :assignedTo')
            ->andWhere('a.done = :done')
            ->setParameter('assignedTo', $assignedTo)
            ->setParameter('done', $done)
            ->addOrderBy('a.dueDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
