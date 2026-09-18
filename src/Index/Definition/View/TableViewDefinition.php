<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\View;


use WebDevelovers\ResourceBundle\Index\Action\ActionDefinitionInterface;

final readonly class TableViewDefinition extends IndexViewDefinition
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $fields
     * @param list<ActionDefinitionInterface> $headerActions
     * @param list<ActionDefinitionInterface> $itemActions
     */
    public function __construct(
        string $name = 'table',
        private array $fields = [],
        private array $headerActions = [],
        private array $itemActions = [],
        private string|null $primaryItemAction = null,
        array $options = [],
    ) {
        parent::__construct($name, 'table', $options);
    }

    /** @return array<string, mixed> */
    public function getFields(): array
    {
        return $this->fields;
    }

    /** @return list<ActionDefinitionInterface> */
    public function getHeaderActions(): array
    {
        return $this->headerActions;
    }

    /** @return list<ActionDefinitionInterface> */
    public function getItemActions(): array
    {
        return $this->itemActions;
    }

    public function getPrimaryItemAction(): string|null
    {
        return $this->primaryItemAction;
    }
}
