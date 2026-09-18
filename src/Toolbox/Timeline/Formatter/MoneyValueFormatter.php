<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use function is_numeric;
use function is_string;
use function number_format;

final class MoneyValueFormatter implements TimelineValueFormatterInterface
{
    public function supports(string $entityClass, string $field, mixed $value, TimelineEntry $entry): bool
    {
        return false;
    }

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string
    {
        $amount = (float) $value;

        return number_format($amount, 2, ',', '.') . ' €';
    }
}
