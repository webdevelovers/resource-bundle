<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function assert;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('routing');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('alias')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('path')->defaultValue(null)->end()
                ->scalarNode('route_name_prefix')->defaultNull()->end()
                ->scalarNode('identifier')->defaultValue('id')->end()
                ->scalarNode('identifier_type')
                    ->defaultValue('uuid')
                    ->validate()
                        ->ifNotInArray(['int', 'uuid'])
                        ->thenInvalid('Invalid "identifier_type" "%s". Allowed values are "int" or "uuid".')
                    ->end()
                ->end()
                ->arrayNode('criteria')
                    ->useAttributeAsKey('identifier')
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->variableNode('form')->cannotBeEmpty()->end()
                ->scalarNode('input')->defaultNull()->end()
                ->scalarNode('output')->defaultNull()->end()
                ->scalarNode('message')->defaultNull()->end()
                ->scalarNode('section')->cannotBeEmpty()->end()
                ->scalarNode('redirect')->cannotBeEmpty()->end()
                ->scalarNode('templates')->cannotBeEmpty()->end()
                ->scalarNode('index')->cannotBeEmpty()->end()
                ->variableNode('permission')
                    ->defaultValue(false)
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => ! is_bool($value) && ! is_string($value))
                        ->thenInvalid('Invalid "permission" "%s". Allowed values are boolean or string.')
                    ->end()
                ->end()
                ->arrayNode('except')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('only')
                    ->scalarPrototype()->end()
                ->end()
                ->variableNode('vars')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
