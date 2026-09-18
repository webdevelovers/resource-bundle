<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Audit;

/**
 * @phpstan-type TimelineMetadata array{
 *     entityClass: class-string,
 *     entityId: string,
 *     action: 'create'|'update',
 *     changedFields: list<string>,
 *     oldValues: array<string, mixed>,
 *     newValues: array<string, mixed>,
 *     relationChanges: array<string, mixed>
 * }
 */
final readonly class TimelineAuditPayload
{
    /**
     * @param class-string $entityClass
     * @param 'create'|'update' $action
     * @param list<string> $changedFields
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $relationChanges
     */
    public function __construct(
        public string $entityClass,
        public string $entityId,
        public string $subjectName,
        public string $resourceAlias,
        public string $action,
        public array $changedFields,
        public array $oldValues,
        public array $newValues,
        public array $relationChanges,
    ) {
    }

    /** @return TimelineMetadata */
    public function toMetadata(): array
    {
        return [
            'entityClass' => $this->entityClass,
            'entityId' => $this->entityId,
            'action' => $this->action,
            'changedFields' => $this->changedFields,
            'oldValues' => $this->oldValues,
            'newValues' => $this->newValues,
            'relationChanges' => $this->relationChanges,
        ];
    }
}
