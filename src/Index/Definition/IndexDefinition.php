<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition;

use LogicException;

use WebDevelovers\ResourceBundle\Index\Definition\Constraint\ConstraintDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;
use function sprintf;

final readonly class IndexDefinition implements IndexDefinitionInterface
{
    /**
     * @param list<ConstraintDefinitionInterface> $constraints
     * @param array<string, FilterDefinitionInterface> $filters
     * @param array<string, SortDefinitionInterface> $sorts
     * @param array<string, IndexViewDefinitionInterface> $views
     */
    public function __construct(
        private string $name,
        private string|null $resourceClass = null,
        private string|null $provider = null,
        private string $defaultView = 'table',
        private int $defaultPerPage = 25,
        private array $constraints = [],
        private array $filters = [],
        private array $sorts = [],
        private array $views = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResourceClass(): string|null
    {
        return $this->resourceClass;
    }

    public function getProvider(): string|null
    {
        return $this->provider;
    }

    public function getDefaultView(): string
    {
        return $this->defaultView;
    }

    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /** @return list<ConstraintDefinitionInterface> */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /** @return array<string, FilterDefinitionInterface> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @return array<string, SortDefinitionInterface> */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /** @return array<string, IndexViewDefinitionInterface> */
    public function getViews(): array
    {
        return $this->views;
    }

    public function hasView(string $name): bool
    {
        return isset($this->views[$name]);
    }

    public function getView(string $name): IndexViewDefinitionInterface
    {
        if (! isset($this->views[$name])) {
            throw new LogicException(sprintf('View "%s" not configured for index "%s".', $name, $this->name));
        }

        return $this->views[$name];
    }
}
