<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Sort;

interface SortDefinitionInterface
{
    public function getName(): string;

    public function getPath(): string|null;

    public function getDefaultDirection(): string;

    /** @return array<string, mixed> */
    public function getOptions(): array;
}
