<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\State;

use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

interface IndexStateNormalizerInterface
{
    public function normalize(IndexState $state, IndexDefinitionInterface $definition): IndexState;
}
