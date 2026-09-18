<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

interface RouteFactoryInterface
{
    public static function generateRouteName(
        string $applicationName,
        string $resourceName,
        string $routeName,
        string|null $sectionName,
        string|null $routeNamePrefix = null,
    ): string;

    public function createRouteCollection(): RouteCollection;

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
    ): Route;
}
