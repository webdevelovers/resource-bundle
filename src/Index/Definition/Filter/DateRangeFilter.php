<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Filter;

final readonly class DateRangeFilter implements FilterDefinitionInterface
{
    public function __construct(
        private string $name,
        private string|null $label = null,
        private string|null $field = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'date_range';
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
        ];
    }
}
