<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\Routing\RouterAvailabilityResolver;

final class RouterAvailabilityResolverTest extends TestCase
{
    public function testIsAvailable(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $routeCollection = new RouteCollection();
        $routeCollection->add('app_product_index', new Route('/products'));

        $router->method('getRouteCollection')->willReturn($routeCollection);

        $configuration = $this->createMock(RequestConfiguration::class);
        $configuration->expects(self::exactly(2))
            ->method('getRouteName')
            ->willReturnMap([
                ['index', 'app_product_index'],
                ['show', 'app_product_show'],
            ]);

        $resolver = new RouterAvailabilityResolver($router);

        self::assertTrue($resolver->isAvailable($configuration, 'index'));
        self::assertFalse($resolver->isAvailable($configuration, 'show'));
    }

    public function testResolve(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $routeCollection = new RouteCollection();
        $routeCollection->add('app_product_index', new Route('/products'));
        $routeCollection->add('app_product_show', new Route('/products/{id}'));

        $router->method('getRouteCollection')->willReturn($routeCollection);

        $configuration = $this->createStub(RequestConfiguration::class);
        $configuration->method('getRouteName')->willReturnMap([
            ['index', 'app_product_index'],
            ['show', 'app_product_show'],
            ['create', 'app_product_create'],
        ]);

        $resolver = new RouterAvailabilityResolver($router);

        $results = $resolver->resolve($configuration, ['index', 'show', 'create']);

        self::assertSame([
            'index' => true,
            'show' => true,
            'create' => false,
        ], $results);
    }
}
