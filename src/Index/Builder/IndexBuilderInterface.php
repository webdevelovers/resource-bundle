<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Builder;

use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;

interface IndexBuilderInterface
{
    public function setDefaultView(string $view): self;

    public function setDefaultPerPage(int $perPage): self;

    public function addConstraint(string $field, mixed $value, string $operator = 'equals'): self;

    /** @param list<FilterDefinitionInterface> $definitions */
    public function addFilters(array $definitions): self;

    public function addFilter(FilterDefinitionInterface $definition): self;

    /** @param list<SortDefinitionInterface> $definitions */
    public function addSorts(array $definitions): self;

    public function addSort(SortDefinitionInterface $definition): self;

    public function addView(IndexViewDefinitionInterface $definition): self;

    public function getDefinition(): IndexDefinitionInterface;
}
