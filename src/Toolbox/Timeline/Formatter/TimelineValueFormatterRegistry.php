<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

use function is_array;
use function is_bool;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class TimelineValueFormatterRegistry
{
    /** @param iterable<TimelineValueFormatterInterface> $formatters */
    public function __construct(
        #[AutowireIterator('wd.resource.timeline_value_formatter')]
        private iterable $formatters,
    ) {
    }

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($entityClass, $field, $value, $entry)) {
                return $formatter->format($entityClass, $field, $value, $entry);
            }
        }

        return $this->fallback($value);
    }

    private function fallback(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sì' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
