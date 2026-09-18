<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use WebDevelovers\ResourceBundle\CRUD\ApplyTransition;
use WebDevelovers\ResourceBundle\CRUD\Create;
use WebDevelovers\ResourceBundle\CRUD\Delete;
use WebDevelovers\ResourceBundle\CRUD\Index;
use WebDevelovers\ResourceBundle\CRUD\Show;
use WebDevelovers\ResourceBundle\CRUD\Update;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use function assert;
use function class_exists;
use function is_array;
use function sprintf;
use function strrpos;
use function strtolower;
use function substr;

final class RegisterResourceActionPass implements CompilerPassInterface
{
    private const array DEFAULT_ACTIONS = [
        'index',
        'show',
        'create',
        'update',
        'delete',
        //'bulk_delete',
        'apply_transition',
    ];

    public function process(ContainerBuilder $container): void
    {
        $this->registerDefaultActions($container);

        try {
            $resources = $container->getParameter('wd.resources');
            assert(is_array($resources));
        } catch (InvalidArgumentException) {
            return;
        }

        foreach ($resources as $alias => $configuration) {
            $metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);

            $this->setClassesParameters($container, $metadata);
            $this->addController($container, $metadata);
        }
    }

    private function registerDefaultActions(ContainerBuilder $container): void
    {
        $this->registerDefaultAction(Index::class, $container);
        $this->registerDefaultAction(Show::class, $container);
        $this->registerDefaultAction(Create::class, $container);
        $this->registerDefaultAction(Update::class, $container);
        $this->registerDefaultAction(Delete::class, $container);
        $this->registerDefaultAction(ApplyTransition::class, $container);
    }

    private function registerDefaultAction(
        string $className,
        ContainerBuilder $container,
    ): void {
        if (! class_exists($className)) {
            return;
        }

        $definition = self::defaultDefinition($className);

        $definitionAliasPrefix = 'wd.resource.action.default_';
        $classNameWithoutFQDN = substr($className, strrpos($className, '\\') + 1);

        $container->setDefinition($definitionAliasPrefix . strtolower($classNameWithoutFQDN), $definition);
    }

    protected function addController(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        foreach (self::DEFAULT_ACTIONS as $action) {
            if (! $metadata->hasAction($action)) {
                continue;
            }

            $definition = self::defaultDefinition($metadata->getAction($action));
            $definitionName = $metadata->getServiceId('resource_action', suffix: $action);
            $container->setDefinition($definitionName, $definition);
        }
    }

    private static function defaultDefinition(string $className): Definition
    {
        $definition = new Definition($className);
        $definition
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addMethodCall('setContainer', [new Reference('service_container')])
            ->setPublic(true)
            ->addTag('controller.service_arguments');

        return $definition;
    }

    protected function setClassesParameters(ContainerBuilder $container, MetadataInterface $metadata): void
    {
        if (! $metadata->hasClass('model')) {
            throw new InvalidArgumentException(sprintf('Resource "%s" does not have a model class.', $metadata->name));
        }

        $container->setParameter(
            sprintf(
                '%s.model.%s.class',
                $metadata->applicationName,
                $metadata->name,
            ),
            $metadata->getClass('model'),
        );

        foreach (self::DEFAULT_ACTIONS as $action) {
            if (! $metadata->hasAction($action)) {
                continue;
            }

            $container->setParameter(
                sprintf(
                '%s.action.%s.%s.class',
                $metadata->applicationName,
                $metadata->name,
                $action,
            ),
                $metadata->getAction($action),
            );
        }
    }
}
