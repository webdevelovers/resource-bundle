<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition;

use WebDevelovers\ResourceBundle\Index\Definition\Constraint\ConstraintDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;

interface IndexDefinitionInterface
{
    public function getName(): string;

    public function getResourceClass(): string|null;

    public function getProvider(): string|null;

    public function getDefaultView(): string;

    public function getDefaultPerPage(): int;

    /** @return list<ConstraintDefinitionInterface> */
    public function getConstraints(): array;

    /** @return array<string, FilterDefinitionInterface> */
    public function getFilters(): array;

    /** @return array<string, SortDefinitionInterface> */
    public function getSorts(): array;

    /** @return array<string, IndexViewDefinitionInterface> */
    public function getViews(): array;

    public function hasView(string $name): bool;

    public function getView(string $name): IndexViewDefinitionInterface;
}
