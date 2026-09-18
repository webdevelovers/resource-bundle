<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

#[AutoconfigureTag('wd.resource.timeline_value_formatter')]
interface TimelineValueFormatterInterface
{
    public function supports(string $entityClass, string $field, mixed $value, TimelineEntry $entry): bool;

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string;
}
