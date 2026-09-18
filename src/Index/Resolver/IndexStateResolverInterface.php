<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Resolver;

use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

interface IndexStateResolverInterface
{
    public function resolve(Request $request, IndexDefinitionInterface $definition): IndexState;
}
