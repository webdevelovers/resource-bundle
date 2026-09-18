<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection\Compiler;

use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Throwable;

use WebDevelovers\ResourceBundle\Attribute\AsResourceIndex;

final class RegisterResourceIndexPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($class);
            } catch (Throwable) {
                continue;
            }

            if ($reflection->getAttributes(AsResourceIndex::class) === []) {
                continue;
            }

            $definition->addTag('app.resource.index');
        }
    }
}
