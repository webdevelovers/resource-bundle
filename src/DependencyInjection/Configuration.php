<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('web_develovers_resource');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('toolbox')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auditing')->defaultTrue()->end()
                        ->booleanNode('activity')->defaultTrue()->end()
                        ->booleanNode('attachment')->defaultTrue()->end()
                        ->booleanNode('bookmark')->defaultTrue()->end()
                        ->booleanNode('follower')->defaultTrue()->end()
                        ->booleanNode('timeline')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
