<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Blame\BlameGeneratorInterface;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

/**
 * @method TimelineEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimelineEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimelineEntry[]    findAll()
 * @method TimelineEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimelineEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimelineEntry::class);
    }

    /** @return TimelineEntry[] */
    public function getTimeline(
        Uuid $subject,
    ): array {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.subject = :subject')
            ->setParameter('subject', $subject, UuidType::NAME)
            ->addOrderBy('t.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function hasSystemCreateEntry(Uuid $subject): bool
    {
        $entry = $this->findOneBy([
            'subject' => $subject,
            'event' => 'create',
            'blameId' => Uuid::fromString(BlameGeneratorInterface::SYSTEM_UUID),
        ]);

        return $entry instanceof TimelineEntry;
    }
}
