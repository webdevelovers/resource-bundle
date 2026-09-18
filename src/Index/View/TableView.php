<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View;

use LogicException;
use WebDevelovers\ResourceBundle\Index\Action\ActionDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\View\TableViewDefinition;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

final readonly class TableView implements IndexViewInterface
{
    public function __construct(
        private IndexViewDefinitionInterface $definition,
        private IndexState $state,
        private IndexResult $result,
    ) {
    }

    public function getName(): string
    {
        return $this->definition->getName();
    }

    public function getType(): string
    {
        return $this->definition->getType();
    }

    public function getDefinition(): IndexViewDefinitionInterface
    {
        return $this->definition;
    }

    /** @return array<string, mixed> */
    public function getFields(): array
    {
        return $this->getTableDefinition()->getFields();
    }

    /** @return list<ActionDefinitionInterface> */
    public function getHeaderActions(): array
    {
        return $this->getTableDefinition()->getHeaderActions();
    }

    /** @return list<ActionDefinitionInterface> */
    public function getItemActions(): array
    {
        return $this->getTableDefinition()->getItemActions();
    }

    public function getPrimaryItemAction(): string|null
    {
        return $this->getTableDefinition()->getPrimaryItemAction();
    }

    public function getState(): IndexState
    {
        return $this->state;
    }

    public function getResult(): IndexResult
    {
        return $this->result;
    }

    private function getTableDefinition(): TableViewDefinition
    {
        if (! $this->definition instanceof TableViewDefinition) {
            throw new LogicException('TableView requires a TableViewDefinition.');
        }

        return $this->definition;
    }
}
