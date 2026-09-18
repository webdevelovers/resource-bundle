<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Action;

final readonly class ActionDefinition implements ActionDefinitionInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $name,
        private string|null $label = null,
        private string|null $icon = null,
        private string|null $route = null,
        private array $options = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string|null
    {
        return $this->label;
    }

    public function getIcon(): string|null
    {
        return $this->icon;
    }

    public function getRoute(): string|null
    {
        return $this->route;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }
}
