<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Filter;

final readonly class EntityFilter implements FilterDefinitionInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $name,
        private string $target,
        private string|null $label = null,
        private string|null $field = null,
        private bool $multiple = false,
        private array $options = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'entity';
    }

    public function getLabel(): string|null
    {
        return $this->label;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return [
            'field' => $this->field ?? $this->name,
            'target' => $this->target,
            'multiple' => $this->multiple,
            ...$this->options,
        ];
    }
}
