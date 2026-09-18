<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Action;

interface ActionDefinitionInterface
{
    public function getName(): string;

    public function getLabel(): string|null;

    public function getIcon(): string|null;

    public function getRoute(): string|null;

    /** @return array<string, mixed> */
    public function getOptions(): array;
}
