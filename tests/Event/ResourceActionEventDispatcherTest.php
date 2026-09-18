<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Event\ResourceActionEvent;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcher;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

final class ResourceActionEventDispatcherTest extends TestCase
{
    public function testDispatchPublishesGlobalAndScopedNames(): void
    {
        $configuration = new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            parameters: new Parameters([]),
        );

        $names = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(
                self::isInstanceOf(ResourceActionEvent::class),
                self::isString(),
            )
            ->willReturnCallback(static function (object $event, string $eventName) use (&$names) {
                $names[] = $eventName;

                return $event;
            });

        (new ResourceActionEventDispatcher($dispatcher))->dispatch(
            configuration: $configuration,
            action: 'create',
            stage: 'initialized',
        );

        self::assertSame([
            'wd.resource.action.create.initialized',
            'wd.resource.action.app.product.create.initialized',
        ], $names);
    }
}

