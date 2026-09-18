<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use function sprintf;

final class RouteFactory implements RouteFactoryInterface
{
    public static function generateRouteName(
        string $applicationName,
        string $resourceName,
        string $routeName,
        string|null $sectionName,
        string|null $routeNamePrefix = null,
    ): string {
        $sectionPrefix = $sectionName ? $sectionName . '_' : '';
        $namePrefix = $routeNamePrefix ? $routeNamePrefix . '_' : '';

        return sprintf(
            '%s_%s%s_%s%s',
            $applicationName,
            $sectionPrefix,
            $resourceName,
            $namePrefix,
            $routeName,
        );
    }

    public function createRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * @param array<string,mixed> $defaults
     * @param array<int,string> $requirements
     * @param array<string,mixed> $options
     * @param array<int,string> $schemes
     * @param array<int,string> $methods
     */
    public function createRoute(
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = '',
    ): Route {
        return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
    }
}
