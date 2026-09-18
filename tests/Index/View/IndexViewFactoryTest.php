<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Index\View;

use LogicException;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\TableViewDefinition;
use WebDevelovers\ResourceBundle\Index\State\IndexState;
use WebDevelovers\ResourceBundle\Index\View\IndexViewFactory;
use WebDevelovers\ResourceBundle\Index\View\TableView;

final class IndexViewFactoryTest extends TestCase
{
    public function testCreateReturnsTableViewForTableDefinition(): void
    {
        $definition = new IndexDefinition(
            name: 'wd.product_index',
            views: [
                'table' => new TableViewDefinition(
                    name: 'table',
                    fields: [
                        'name' => ['type' => 'string', 'label' => 'Nome'],
                    ],
                ),
            ],
        );

        $state = new IndexState(view: 'table');
        $result = new IndexResult(items: [], page: 1, perPage: 25, totalItems: 0);

        $view = (new IndexViewFactory())->create($definition, $state, $result);

        self::assertInstanceOf(TableView::class, $view);
        self::assertSame('table', $view->getName());
        self::assertSame('table', $view->getType());
        self::assertSame(['name' => ['type' => 'string', 'label' => 'Nome']], $view->getFields());
        self::assertSame([], $view->getHeaderActions());
        self::assertSame([], $view->getItemActions());
        self::assertNull($view->getPrimaryItemAction());
        self::assertSame($state, $view->getState());
        self::assertSame($result, $view->getResult());
    }

    public function testCreateThrowsForUnsupportedViewType(): void
    {
        $definition = new IndexDefinition(
            name: 'wd.product_index',
            views: [
                'cards' => new IndexViewDefinition('cards', 'cards'),
            ],
        );

        $state = new IndexState(view: 'cards');
        $result = new IndexResult(items: [], page: 1, perPage: 25, totalItems: 0);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsupported index view type "cards".');

        (new IndexViewFactory())->create($definition, $state, $result);
    }
}
