<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View;

use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

interface IndexViewFactoryInterface
{
    public function create(
        IndexDefinitionInterface $definition,
        IndexState $state,
        IndexResult $result,
    ): IndexViewInterface;
}
