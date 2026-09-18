<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Bookmark;

/**
 * @method Bookmark|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bookmark|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bookmark[]    findAll()
 * @method Bookmark[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookmarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bookmark::class);
    }

    /** @return Bookmark[] */
    public function getBookmarks(
        UserInterface $user,
    ): array {
        $qb = $this->createQueryBuilder('b');
        $qb->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('b.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
