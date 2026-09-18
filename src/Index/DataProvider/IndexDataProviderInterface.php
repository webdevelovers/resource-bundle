<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\DataProvider;

use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

interface IndexDataProviderInterface
{
    /** @param array<string, mixed> $context */
    public function provide(
        IndexDefinitionInterface $definition,
        IndexState $state,
        array $context = [],
    ): IndexResult;
}
