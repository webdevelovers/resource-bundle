<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;

/**
 * @method Attachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attachment[]    findAll()
 * @method Attachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    /** @return Attachment[] */
    public function getAttachments(
        Uuid $subject,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.subjectId = :subjectId')
            ->setParameter('subjectId', $subject, UuidType::NAME)
            ->addOrderBy('a.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
