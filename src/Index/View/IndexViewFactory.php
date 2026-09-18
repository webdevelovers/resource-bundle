<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View;

use LogicException;

use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;
use function sprintf;

final class IndexViewFactory implements IndexViewFactoryInterface
{
    public function create(
        IndexDefinitionInterface $definition,
        IndexState $state,
        IndexResult $result,
    ): IndexViewInterface {
        $viewDefinition = $definition->getView($state->view);

        return match ($viewDefinition->getType()) {
            'table' => new TableView($viewDefinition, $state, $result),
            default => throw new LogicException(sprintf(
                'Unsupported index view type "%s".',
                $viewDefinition->getType(),
            )),
        };
    }
}
