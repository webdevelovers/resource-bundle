<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use WebDevelovers\ResourceBundle\Routing\RouteFactory;

final class RouteFactoryTest extends TestCase
{
    private RouteFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new RouteFactory();
    }

    public function testGenerateRouteName(): void
    {
        self::assertSame(
            'app_admin_product_index',
            RouteFactory::generateRouteName('app', 'product', 'index', 'admin')
        );

        self::assertSame(
            'app_product_index',
            RouteFactory::generateRouteName('app', 'product', 'index', null)
        );

        self::assertSame(
            'app_admin_product_api_index',
            RouteFactory::generateRouteName('app', 'product', 'index', 'admin', 'api')
        );

        self::assertSame(
            'app_admin_product_index',
            RouteFactory::generateRouteName('app', 'product', 'index', 'admin', '')
        );
    }

    public function testCreateRouteCollection(): void
    {
        self::assertInstanceOf(RouteCollection::class, $this->factory->createRouteCollection());
    }

    public function testCreateRoute(): void
    {
        $path = '/test';
        $defaults = ['_controller' => 'App\Controller\TestController'];
        $requirements = ['id' => '\d+'];
        $options = ['utf8' => true];
        $host = 'localhost';
        $schemes = ['https'];
        $methods = ['GET'];
        $condition = "request.query.get('foo') == 'bar'";

        $route = $this->factory->createRoute(
            $path,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition
        );

        self::assertInstanceOf(Route::class, $route);
        self::assertSame($path, $route->getPath());
        self::assertSame($defaults, $route->getDefaults());
        self::assertSame($requirements, $route->getRequirements());
        self::assertSame($options['utf8'], $route->getOption('utf8'));
        self::assertSame($host, $route->getHost());
        self::assertSame($schemes, $route->getSchemes());
        self::assertSame($methods, $route->getMethods());
        self::assertSame($condition, $route->getCondition());
    }
}
