<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Follower;

/**
 * @method Follower|null find($id, $lockMode = null, $lockVersion = null)
 * @method Follower|null findOneBy(array $criteria, array $orderBy = null)
 * @method Follower[]    findAll()
 * @method Follower[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FollowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follower::class);
    }

    /** @return Follower[] */
    public function getFollowers(
        Uuid $subject,
    ): array {
        $qb = $this->createQueryBuilder('f');
        $qb->andWhere('f.subject = :subject')
            ->setParameter('subject', $subject, UuidType::NAME);

        return $qb->getQuery()->getResult();
    }
}
