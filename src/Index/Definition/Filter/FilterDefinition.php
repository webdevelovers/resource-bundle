<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Filter;

/**
 * The filter definition used to define a filter for a table, meaning filters that the user can apply dynamically to the data.
 * A filter is used to filter the data in a table. It can be used to filter the data by a specific value, or by a range of values.
 */
final readonly class FilterDefinition implements FilterDefinitionInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $name,
        private string $type,
        private string|null $label = null,
        private array $options = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string|null
    {
        return $this->label;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
