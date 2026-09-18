<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use DateTimeImmutable;
use Throwable;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

use function is_string;
use function preg_match;

final class IsoDateTimeValueFormatter implements TimelineValueFormatterInterface
{
    public function supports(string $entityClass, string $field, mixed $value, TimelineEntry $entry): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value) === 1;
    }

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string
    {
        try {
            $date = new DateTimeImmutable((string) $value);
        } catch (Throwable) {
            return (string) $value;
        }

        if ($date->format('H:i:s') === '00:00:00') {
            return $date->format('d/m/Y');
        }

        return $date->format('d/m/Y H:i');
    }
}
