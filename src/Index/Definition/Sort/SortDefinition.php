<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Sort;

/**
 * The sort definition used to define a sort for a table, meaning sorts that the user can apply dynamically to the data.
 */
final readonly class SortDefinition implements SortDefinitionInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $name,
        private string|null $path = null,
        private string $defaultDirection = 'asc',
        private array $options = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string|null
    {
        return $this->path;
    }

    public function getDefaultDirection(): string
    {
        return $this->defaultDirection;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
