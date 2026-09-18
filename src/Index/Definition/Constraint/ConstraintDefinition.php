<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Constraint;

/**
 * Adding mandatory data constraints: e.g. immutable constraint clauses, always applied to table data before filtering.
 * For example only picking some kind of "enabled" data, discarding disabled ones. Or data with a particular type or status for a table.
 */
final readonly class ConstraintDefinition implements ConstraintDefinitionInterface
{
    public function __construct(
        private string $field,
        private string $operator,
        private mixed $value,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
