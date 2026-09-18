<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View;

use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

interface IndexViewInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getDefinition(): IndexViewDefinitionInterface;

    /** @return array<string, mixed> */
    public function getFields(): array;

    public function getState(): IndexState;

    public function getResult(): IndexResult;
}
