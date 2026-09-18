<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\View;

interface IndexViewDefinitionInterface
{
    public function getName(): string;

    public function getType(): string;

    /** @return array<string, mixed> */
    public function getOptions(): array;
}
