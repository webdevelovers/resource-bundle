<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

use function is_array;
use function is_bool;
use function json_encode;

use const JSON_THROW_ON_ERROR;

#[AsTaggedItem(priority: -255)]
final class ScalarValueFormatter implements TimelineValueFormatterInterface
{
    public function supports(string $entityClass, string $field, mixed $value, TimelineEntry $entry): bool
    {
        return true;
    }

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string
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
