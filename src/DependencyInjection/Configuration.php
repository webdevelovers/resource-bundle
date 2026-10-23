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
                ->arrayNode('crud')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('show')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('toolbox_mode')->values(['enabled', 'offcanvas', 'disabled'])->defaultValue('enabled')->end()
                                ->integerNode('main_columns')->min(1)->max(12)->defaultValue(8)->end()
                                ->integerNode('toolbox_columns')->min(1)->max(12)->defaultValue(4)->end()
                                ->integerNode('offcanvas_width')->min(320)->max(1200)->defaultValue(560)->end()
                            ->end()
                        ->end()
                        ->arrayNode('edit')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('toolbox_mode')->values(['enabled', 'offcanvas', 'disabled'])->defaultValue('enabled')->end()
                                ->integerNode('main_columns')->min(1)->max(12)->defaultValue(8)->end()
                                ->integerNode('toolbox_columns')->min(1)->max(12)->defaultValue(4)->end()
                                ->integerNode('offcanvas_width')->min(320)->max(1200)->defaultValue(560)->end()
                            ->end()
                        ->end()
                        ->arrayNode('create')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('toolbox_mode')->values(['enabled', 'offcanvas', 'disabled'])->defaultValue('disabled')->end()
                                ->integerNode('main_columns')->min(1)->max(12)->defaultValue(8)->end()
                                ->integerNode('toolbox_columns')->min(1)->max(12)->defaultValue(4)->end()
                                ->integerNode('offcanvas_width')->min(320)->max(1200)->defaultValue(560)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
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
