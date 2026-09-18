<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View\Field;

interface FieldRendererInterface
{
    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $context
     */
    public function render(
        object $resource,
        string $name,
        array $field,
        mixed $value,
        array $context = [],
    ): string;
}
