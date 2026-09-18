<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Registry;

use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

interface IndexRegistryInterface
{
    public function get(string $name): IndexDefinitionInterface;

    public function has(string $name): bool;
}
