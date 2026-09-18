<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\DataProvider\Option;

use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

interface FilterOptionProviderInterface
{
    /**
     * @param array<string> $selected
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getOptions(
        IndexDefinitionInterface $definition,
        string $filterName,
        string $query = '',
        array $selected = [],
    ): array;
}
