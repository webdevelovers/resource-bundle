<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\View;

readonly class IndexViewDefinition implements IndexViewDefinitionInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $name,
        private string $type,
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

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
