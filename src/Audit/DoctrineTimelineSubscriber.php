<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Audit;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use WebDevelovers\ResourceBundle\Blame\BlameGeneratorInterface;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use function strtolower;

#[AsDoctrineListener(event: Events::onFlush, connection: 'default')]
final class DoctrineTimelineSubscriber
{
    private bool $isHandling = false;

    public function __construct(
        private readonly DoctrineTimelinePayloadExtractor $payloadExtractor,
        private readonly BlameGeneratorInterface $blameGenerator,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isHandling) {
            return;
        }

        $entityManager = $args->getObjectManager();
        if (! $entityManager instanceof EntityManagerInterface) {
            return;
        }

        $this->isHandling = true;

        try {
            $this->createTimelineEntries($entityManager, $entityManager->getUnitOfWork());
        } finally {
            $this->isHandling = false;
        }
    }

    private function createTimelineEntries(EntityManagerInterface $entityManager, UnitOfWork $unitOfWork): void
    {
        $payloads = $this->payloadExtractor->extract($unitOfWork);
        if ($payloads === []) {
            return;
        }

        $timelineMetadata = $entityManager->getClassMetadata(TimelineEntry::class);

        foreach ($payloads as $payload) {
            if ($payload->action === 'create') {
                continue;
            }

            $entry = new TimelineEntry(
                event: 'audit.' . strtolower($payload->action),
                resourceReference: new ResourceReference(
                    subjectId: $payload->entityId,
                    subjectName: $payload->subjectName,
                    resourceAlias: $payload->resourceAlias,
                ),
                blame: $this->blameGenerator->generate(),
                metadata: $payload->toMetadata(),
            );

            $entityManager->persist($entry);
            $unitOfWork->computeChangeSet($timelineMetadata, $entry);
        }
    }
}
