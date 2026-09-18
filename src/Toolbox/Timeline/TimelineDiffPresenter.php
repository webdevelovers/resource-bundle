<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline;

use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\TimelineValueFormatterRegistry;

use function array_diff;
use function count;
use function is_array;
use function is_string;
use function sprintf;

final readonly class TimelineDiffPresenter
{
    public function __construct(
        private TimelineValueFormatterRegistry $formatterRegistry,
    ) {
    }

    /** @return array<int, array{field: string, label: string, old: string, new: string}> */
    public function buildRows(TimelineEntry $entry): array
    {
        $metadata = $entry->metadata;
        $entityClass = (string) ($metadata['entityClass'] ?? '');
        $changedFields = $metadata['changedFields'] ?? [];
        $oldValues = $metadata['oldValues'] ?? [];
        $newValues = $metadata['newValues'] ?? [];

        if (! is_array($changedFields) || ! is_array($oldValues) || ! is_array($newValues)) {
            return [];
        }

        $rows = [];
        foreach ($changedFields as $field) {
            if (! is_string($field)) {
                continue;
            }

            $old = $oldValues[$field] ?? null;
            $new = $newValues[$field] ?? null;

            if ($this->isUuidCollection($old) && $this->isUuidCollection($new)) {
                $rows[] = [
                    'field' => $field,
                    'label' => 'wd.field.' . $field,
                    'old' => $this->formatCollectionSummary($old),
                    'new' => $this->formatCollectionSummaryWithDelta($old, $new),
                ];

                continue;
            }

            $rows[] = [
                'field' => $field,
                'label' => 'wd.field.' . $field,
                'old' => $this->formatterRegistry->format($entityClass, $field, $old, $entry),
                'new' => $this->formatterRegistry->format($entityClass, $field, $new, $entry),
            ];
        }

        return $rows;
    }

    private function isUuidCollection(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item) || ! Uuid::isValid($item)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, string> $value */
    private function formatCollectionSummary(array $value): string
    {
        return sprintf('%d elementi', count($value));
    }

    /**
     * @param array<int, string> $old
     * @param array<int, string> $new
     */
    private function formatCollectionSummaryWithDelta(array $old, array $new): string
    {
        $added = count(array_diff($new, $old));
        $removed = count(array_diff($old, $new));

        if ($added === 0 && $removed === 0) {
            return $this->formatCollectionSummary($new);
        }

        if ($removed === 0) {
            return sprintf('%d elementi (+%d)', count($new), $added);
        }

        if ($added === 0) {
            return sprintf('%d elementi (-%d)', count($new), $removed);
        }

        return sprintf('%d elementi (+%d, -%d)', count($new), $added, $removed);
    }
}
