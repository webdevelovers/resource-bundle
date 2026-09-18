<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Filter;

interface FilterDefinitionInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getLabel(): string|null;

    /** @return array<string, mixed> */
    public function getOptions(): array;
}
