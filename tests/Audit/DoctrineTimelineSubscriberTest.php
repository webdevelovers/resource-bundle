<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Audit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelinePayloadExtractor;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelineSubscriber;
use WebDevelovers\ResourceBundle\Audit\DoctrineValueNormalizer;
use WebDevelovers\ResourceBundle\Blame\Blame;
use WebDevelovers\ResourceBundle\Blame\BlameGeneratorInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;

#[AllowMockObjectsWithoutExpectations]
final class DoctrineTimelineSubscriberTest extends TestCase
{
    public function testOnFlushPersistsTimelineEntryOnlyForNonCreatePayloads(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Example';
            }
        };

        $payloadExtractor = $this->createExtractorForResource($resource);

        $blameGenerator = $this->createMock(BlameGeneratorInterface::class);
        $blameGenerator->expects(self::once())->method('generate')->willReturn(new Blame('0d56b3cd-91f2-4ddc-a0f4-6f7dfd5fb65e', 'user@example.com', 'main', '127.0.0.1'));

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $timelineMetadata = $this->createMock(ClassMetadata::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$resource]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);
        $unitOfWork->method('getSingleIdentifierValue')->with($resource)->willReturn('4f8d14d5-5e72-4d8f-95bb-bf29fd9b6046');
        $unitOfWork->method('getEntityChangeSet')->with($resource)->willReturn([
            'name' => ['A', 'B'],
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($timelineMetadata);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (mixed $entry): bool {
                return is_object($entry)
                    && property_exists($entry, 'event')
                    && $entry->event === 'audit.update';
            }));

        $unitOfWork->expects(self::once())
            ->method('computeChangeSet')
            ->with($timelineMetadata, self::isObject());

        $subscriber = new DoctrineTimelineSubscriber($payloadExtractor, $blameGenerator);
        $subscriber->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushDoesNothingWhenThereAreNoPayloads(): void
    {
        $payloadExtractor = new DoctrineTimelinePayloadExtractor(
            new DoctrineValueNormalizer(),
            $this->createMock(MetadataRegistryInterface::class),
        );

        $blameGenerator = $this->createMock(BlameGeneratorInterface::class);
        $blameGenerator->expects(self::never())->method('generate');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([new \stdClass()]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([new \stdClass()]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->expects(self::never())->method('persist');

        $subscriber = new DoctrineTimelineSubscriber($payloadExtractor, $blameGenerator);
        $subscriber->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushIsGuardedAgainstReEntrancy(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Example';
            }
        };

        $payloadExtractor = $this->createExtractorForResource($resource);

        $blameGenerator = $this->createMock(BlameGeneratorInterface::class);
        $blameGenerator->expects(self::once())->method('generate')->willReturn(new Blame('0d56b3cd-91f2-4ddc-a0f4-6f7dfd5fb65e', 'user@example.com', 'main', '127.0.0.1'));

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $timelineMetadata = $this->createMock(ClassMetadata::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);
        $unitOfWork->method('getSingleIdentifierValue')->with($resource)->willReturn('4f8d14d5-5e72-4d8f-95bb-bf29fd9b6046');
        $unitOfWork->method('getEntityChangeSet')->with($resource)->willReturn([
            'name' => ['A', 'B'],
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($timelineMetadata);
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function () use (&$subscriber, $entityManager): void {
                $subscriber->onFlush(new OnFlushEventArgs($entityManager));
            });

        $unitOfWork->expects(self::once())
            ->method('computeChangeSet')
            ->with($timelineMetadata, self::isObject());

        $subscriber = new DoctrineTimelineSubscriber($payloadExtractor, $blameGenerator);
        $subscriber->onFlush(new OnFlushEventArgs($entityManager));
    }

    private function createExtractorForResource(ResourceInterface $resource): DoctrineTimelinePayloadExtractor
    {
        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.example');

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('getByClass')
            ->willReturnCallback(static function (string $class) use ($resource, $metadata): MetadataInterface {
                if ($class !== $resource::class) {
                    throw new InvalidArgumentException();
                }

                return $metadata;
            });

        return new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);
    }
}
