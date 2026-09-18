<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection\Compiler;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use WebDevelovers\ResourceBundle\Routing\AutoResourceRouteLoader;
use WebDevelovers\ResourceBundle\Routing\ResourceLoader;
use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

use function array_is_list;
use function assert;
use function class_exists;
use function is_array;
use function is_dir;
use function is_string;
use function str_ends_with;
use function str_replace;
use function strlen;
use function substr;

final class RegisterResourceRoutesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        assert(is_string($projectDir));

        $configurations = [];

        foreach ($this->findRouteClasses($projectDir . '/src/Resource/Route') as $className) {
            if (! class_exists($className)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($className);

            if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
                continue;
            }

            if ($reflectionClass->implementsInterface(ResourceRouteInterface::class)) {
                $config = $className::config();
                if (array_is_list($config) && isset($config[0]) && is_array($config[0])) {
                    foreach ($config as $subConfig) {
                        if (is_array($subConfig)) {
                            $configurations[] = $subConfig;
                        }
                    }
                } else {
                    $configurations[] = $config;
                }
            }
        }

        $container->setParameter('wd.resource_routes', $configurations);

        if ($container->hasDefinition('routing.loader')) {
            $container->register(AutoResourceRouteLoader::class, AutoResourceRouteLoader::class)
                ->setDecoratedService('routing.loader')
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setArgument('$delegatingLoader', new Reference(AutoResourceRouteLoader::class . '.inner'))
                ->setArgument('$resourceLoader', new Reference(ResourceLoader::class));
        }
    }

    /** @return list<class-string> */
    private function findRouteClasses(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $pathname = $file->getPathname();

            if (! str_ends_with($pathname, '.php')) {
                continue;
            }

            $relativePath = substr($pathname, strlen($directory) + 1, -4);
            $classes[] = 'App\\Resource\\Route\\' . str_replace('/', '\\', $relativePath);
        }

        return $classes;
    }
}
