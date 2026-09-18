<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Constraint;

interface ConstraintDefinitionInterface
{
    public function getField(): string;

    public function getOperator(): string;

    public function getValue(): mixed;
}
