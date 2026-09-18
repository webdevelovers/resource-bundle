<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Audit;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use InvalidArgumentException;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_scalar;
use function is_string;
use function spl_object_id;

final readonly class DoctrineTimelinePayloadExtractor
{
    public function __construct(
        private DoctrineValueNormalizer $valueNormalizer,
        private MetadataRegistryInterface $resourceRegistry,
    ) {
    }

    /** @return list<TimelineAuditPayload> */
    public function extract(UnitOfWork $unitOfWork): array
    {
        /** @var array<int, array<string, mixed>> $relationChangesByObjectId */
        $relationChangesByObjectId = [];

        /** @var list<PersistentCollection> $changedCollections */
        $changedCollections = [
            ...$unitOfWork->getScheduledCollectionUpdates(),
            ...$unitOfWork->getScheduledCollectionDeletions(),
        ];

        foreach ($changedCollections as $collection) {
            $owner = $collection->getOwner();
            if (! $owner instanceof ResourceInterface || $owner instanceof TimelineEntry) {
                continue;
            }

            $mapping = $collection->getMapping();
            if (! is_string($mapping['fieldName'] ?? null)) {
                continue;
            }

            $fieldName = $mapping['fieldName'];
            $insertDiff = array_map($this->valueNormalizer->normalize(...), $collection->getInsertDiff());
            $deleteDiff = array_map($this->valueNormalizer->normalize(...), $collection->getDeleteDiff());

            if ($insertDiff === [] && $deleteDiff === []) {
                continue;
            }

            $relationChangesByObjectId[spl_object_id($owner)][$fieldName] = [
                'type' => ($mapping['type'] ?? null) === ClassMetadata::MANY_TO_MANY ? 'many_to_many' : 'one_to_many',
                'added' => $insertDiff,
                'removed' => $deleteDiff,
            ];
        }

        $payloads = [];

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $payload = $this->buildPayload(
                $unitOfWork,
                $entity,
                'create',
                $relationChangesByObjectId[spl_object_id($entity)] ?? [],
            );
            if ($payload === null) {
                continue;
            }

            $payloads[] = $payload;
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $payload = $this->buildPayload(
                $unitOfWork,
                $entity,
                'update',
                $relationChangesByObjectId[spl_object_id($entity)] ?? [],
            );
            if ($payload === null) {
                continue;
            }

            $payloads[] = $payload;
        }

        return $payloads;
    }

    /**
     * @param 'create'|'update' $action
     * @param array<string, mixed> $relationChanges
     */
    private function buildPayload(
        UnitOfWork $unitOfWork,
        object $entity,
        string $action,
        array $relationChanges,
    ): TimelineAuditPayload|null {
        if (! $entity instanceof ResourceInterface || $entity instanceof TimelineEntry) {
            return null;
        }

        $resourceAlias = $this->resolveResourceAlias($entity::class);
        if ($resourceAlias === null) {
            return null;
        }

        $entityId = $unitOfWork->getSingleIdentifierValue($entity);
        $normalizedEntityId = $this->valueNormalizer->normalize($entityId);
        if (! is_scalar($normalizedEntityId)) {
            return null;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($unitOfWork->getEntityChangeSet($entity) as $field => $change) {
            if (in_array($field, ['updatedAt', 'createdAt'], true)) {
                continue;
            }

            $oldValues[$field] = $this->valueNormalizer->normalize($change[0] ?? null);
            $newValues[$field] = $this->valueNormalizer->normalize($change[1] ?? null);
        }

        $changedFields = [...array_keys($oldValues), ...array_keys($relationChanges)];
        if ($action === 'update' && $changedFields === []) {
            return null;
        }

        return new TimelineAuditPayload(
            entityClass: $entity::class,
            entityId: (string) $normalizedEntityId,
            subjectName: (string) $entity,
            resourceAlias: $resourceAlias,
            action: $action,
            changedFields: array_values(array_unique($changedFields)),
            oldValues: $oldValues,
            newValues: $newValues,
            relationChanges: $relationChanges,
        );
    }

    /** @param class-string $entityClass */
    private function resolveResourceAlias(string $entityClass): string|null
    {
        try {
            return $this->resourceRegistry->getByClass($entityClass)->getAlias();
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
