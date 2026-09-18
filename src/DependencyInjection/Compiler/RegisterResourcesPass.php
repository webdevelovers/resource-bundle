<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection\Compiler;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use WebDevelovers\ResourceBundle\Attribute\AsResource;
use function assert;
use function is_dir;
use function is_string;
use function str_ends_with;
use function str_replace;
use function strlen;
use function substr;

final class RegisterResourcesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        try {
            $registry = $container->findDefinition('wd.resource_registry');
        } catch (InvalidArgumentException) {
            return;
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        assert(is_string($projectDir));

        $resources = [];

        foreach ($this->findEntityClasses($projectDir . '/src/Entity') as $className) {
            $reflectionClass = new ReflectionClass($className);
            $attributes = $reflectionClass->getAttributes(AsResource::class);

            if ($attributes === []) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            assert($attribute instanceof AsResource);
            $configuration = $attribute->asConfiguration($className);

            $resources[$attribute->alias] = $configuration;
            $registry->addMethodCall('addFromAliasAndConfiguration', [$attribute->alias, $configuration]);
        }

        $container->setParameter('wd.resources', $resources);
    }

    /** @return list<class-string> */
    private function findEntityClasses(string $directory): array
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
            $classes[] = 'App\\Entity\\' . str_replace('/', '\\', $relativePath);
        }

        return $classes;
    }
}
