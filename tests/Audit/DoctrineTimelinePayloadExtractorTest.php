<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Audit;

use Doctrine\ORM\UnitOfWork;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelinePayloadExtractor;
use WebDevelovers\ResourceBundle\Audit\DoctrineValueNormalizer;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;

#[AllowMockObjectsWithoutExpectations]
final class DoctrineTimelinePayloadExtractorTest extends TestCase
{
    public function testExtractBuildsPayloadForResourceUpdate(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Resource Name';
            }
        };

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);
        $unitOfWork->method('getSingleIdentifierValue')->with($resource)->willReturn(Uuid::fromString('5f59f3a5-171c-4fca-a056-7fbc6f7bc38d'));
        $unitOfWork->method('getEntityChangeSet')->with($resource)->willReturn([
            'name' => ['Old Name', 'New Name'],
            'updatedAt' => ['ignored-old', 'ignored-new'],
        ]);

        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.resource');

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('getByClass')->with($resource::class)->willReturn($metadata);

        $extractor = new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);
        $payloads = $extractor->extract($unitOfWork);

        self::assertCount(1, $payloads);
        self::assertSame('update', $payloads[0]->action);
        self::assertSame('5f59f3a5-171c-4fca-a056-7fbc6f7bc38d', $payloads[0]->entityId);
        self::assertSame(['name'], $payloads[0]->changedFields);
        self::assertSame(['name' => 'Old Name'], $payloads[0]->oldValues);
        self::assertSame(['name' => 'New Name'], $payloads[0]->newValues);
        self::assertSame([], $payloads[0]->relationChanges);
    }

    public function testExtractSkipsEntityWhenAliasCannotBeResolved(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Resource Name';
            }
        };

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('getByClass')->with($resource::class)->willThrowException(new InvalidArgumentException());

        $extractor = new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);

        self::assertSame([], $extractor->extract($unitOfWork));
    }

    public function testExtractSkipsUpdateWithoutChanges(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Resource Name';
            }
        };

        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.resource');

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('getByClass')->willReturn($metadata);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);
        $unitOfWork->method('getSingleIdentifierValue')->with($resource)->willReturn('42');
        $unitOfWork->method('getEntityChangeSet')->with($resource)->willReturn([
            'updatedAt' => ['old', 'new'],
            'createdAt' => ['old', 'new'],
        ]);

        $extractor = new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);

        self::assertSame([], $extractor->extract($unitOfWork));
    }

    public function testExtractSkipsEntityWhenNormalizedIdentifierIsNotScalar(): void
    {
        $resource = new class () implements ResourceInterface {
            public function __toString(): string
            {
                return 'Resource Name';
            }
        };

        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.resource');

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('getByClass')->willReturn($metadata);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$resource]);
        $unitOfWork->method('getSingleIdentifierValue')->with($resource)->willReturn(new class () {
            /** @return array{int} */
            public function getId(): array
            {
                return [1];
            }
        });
        $unitOfWork->method('getEntityChangeSet')->with($resource)->willReturn([
            'name' => ['old', 'new'],
        ]);

        $extractor = new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);

        self::assertSame([], $extractor->extract($unitOfWork));
    }

    public function testExtractSkipsNonResourceEntities(): void
    {
        $entity = new \stdClass();

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);

        $extractor = new DoctrineTimelinePayloadExtractor(new DoctrineValueNormalizer(), $registry);

        self::assertSame([], $extractor->extract($unitOfWork));
    }
}
