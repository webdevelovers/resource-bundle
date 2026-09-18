<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Builder;

use WebDevelovers\ResourceBundle\Index\Definition\Constraint\ConstraintDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\Constraint\ConstraintDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;

final class IndexBuilder implements IndexBuilderInterface
{
    /** @var list<ConstraintDefinitionInterface> */
    private array $constraints = [];

    /** @var array<string, FilterDefinitionInterface> */
    private array $filters = [];

    /** @var array<string, SortDefinitionInterface> */
    private array $sorts = [];

    /** @var array<string, IndexViewDefinitionInterface> */
    private array $views = [];

    private string $defaultView = 'table';

    private int $defaultPerPage = 25;

    public function __construct(
        private readonly string $name,
        private readonly string $resourceClass,
        private readonly string|null $provider = null,
    ) {
    }

    public function setDefaultView(string $view): self
    {
        $this->defaultView = $view;

        return $this;
    }

    public function setDefaultPerPage(int $perPage): self
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    public function addConstraint(string $field, mixed $value, string $operator = 'equals'): self
    {
        $this->constraints[] = new ConstraintDefinition(
            field: $field,
            operator: $operator,
            value: $value,
        );

        return $this;
    }

    /** @param list<FilterDefinitionInterface> $definitions */
    public function addFilters(array $definitions): self
    {
        foreach ($definitions as $definition) {
            $this->addFilter($definition);
        }

        return $this;
    }

    public function addFilter(FilterDefinitionInterface $definition): self
    {
        $this->filters[$definition->getName()] = $definition;

        return $this;
    }

    /** @param list<SortDefinitionInterface> $definitions */
    public function addSorts(array $definitions): self
    {
        foreach ($definitions as $definition) {
            $this->addSort($definition);
        }

        return $this;
    }

    public function addSort(SortDefinitionInterface $definition): self
    {
        $this->sorts[$definition->getName()] = $definition;

        return $this;
    }

    public function addView(IndexViewDefinitionInterface $definition): self
    {
        $this->views[$definition->getName()] = $definition;

        return $this;
    }

    public function getDefinition(): IndexDefinitionInterface
    {
        return new IndexDefinition(
            name: $this->name,
            resourceClass: $this->resourceClass,
            provider: $this->provider,
            defaultView: $this->defaultView,
            defaultPerPage: $this->defaultPerPage,
            constraints: $this->constraints,
            filters: $this->filters,
            sorts: $this->sorts,
            views: $this->views,
        );
    }
}
