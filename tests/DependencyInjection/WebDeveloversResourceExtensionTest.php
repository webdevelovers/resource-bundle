<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebDevelovers\ResourceBundle\DependencyInjection\WebDeveloversResourceExtension;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\DoctrineRelationValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\IsoDateTimeValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\MoneyValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\ScalarValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\TimelineValueFormatterRegistry;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\TimelineDiffPresenter;

final class WebDeveloversResourceExtensionTest extends TestCase
{
    public function testLoadRegistersTimelineServicesAndFormatters(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(TimelineDiffPresenter::class));
        self::assertTrue($container->hasDefinition(TimelineValueFormatterRegistry::class));
        self::assertTrue($container->hasDefinition(DoctrineRelationValueFormatter::class));
        self::assertTrue($container->hasDefinition(IsoDateTimeValueFormatter::class));
        self::assertTrue($container->hasDefinition(MoneyValueFormatter::class));
        self::assertTrue($container->hasDefinition(ScalarValueFormatter::class));

        self::assertSame([
            ['priority' => 100],
        ], $container->getDefinition(DoctrineRelationValueFormatter::class)->getTag('wd.resource.timeline_value_formatter'));
        self::assertNotEmpty($container->getDefinition(IsoDateTimeValueFormatter::class)->getTag('wd.resource.timeline_value_formatter'));
        self::assertNotEmpty($container->getDefinition(MoneyValueFormatter::class)->getTag('wd.resource.timeline_value_formatter'));
        self::assertSame([
            ['priority' => -255],
        ], $container->getDefinition(ScalarValueFormatter::class)->getTag('wd.resource.timeline_value_formatter'));
    }

    public function testPrependRegistersBundleTwigNamespaceAndToolboxMappingsByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $extension->prepend($container);

        self::assertSame([
            [
                'paths' => [
                    dirname(__DIR__, 2) . '/templates' => 'WebDeveloversResource',
                ],
            ],
        ], $container->getExtensionConfig('twig'));

        self::assertSame([
            [
                'orm' => [
                    'mappings' => [
                        'Toolbox' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => dirname(__DIR__, 2) . '/src/Toolbox/Entity',
                            'prefix' => 'WebDevelovers\\ResourceBundle\\Toolbox\\Entity',
                            'alias' => 'Toolbox',
                        ],
                    ],
                ],
            ],
        ], $container->getExtensionConfig('doctrine'));
    }

    public function testPrependRegistersOnlyEnabledToolboxMappings(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $container->prependExtensionConfig($extension->getAlias(), [
            'toolbox' => [
                'auditing' => false,
                'activity' => false,
                'attachment' => true,
                'bookmark' => false,
                'follower' => false,
                'timeline' => true,
            ],
        ]);

        $extension->prepend($container);

        self::assertSame([
            [
                'orm' => [
                    'mappings' => [
                        'Toolbox' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => dirname(__DIR__, 2) . '/src/Toolbox/Entity',
                            'prefix' => 'WebDevelovers\\ResourceBundle\\Toolbox\\Entity',
                            'alias' => 'Toolbox',
                        ],
                    ],
                ],
            ],
        ], $container->getExtensionConfig('doctrine'));
    }

    public function testPrependRegistersTimelineMappingWhenOnlyAuditingIsEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $container->prependExtensionConfig($extension->getAlias(), [
            'toolbox' => [
                'auditing' => true,
                'activity' => false,
                'attachment' => false,
                'bookmark' => false,
                'follower' => false,
                'timeline' => false,
            ],
        ]);

        $extension->prepend($container);

        self::assertSame([
            [
                'orm' => [
                    'mappings' => [
                        'Toolbox' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => dirname(__DIR__, 2) . '/src/Toolbox/Entity',
                            'prefix' => 'WebDevelovers\\ResourceBundle\\Toolbox\\Entity',
                            'alias' => 'Toolbox',
                        ],
                    ],
                ],
            ],
        ], $container->getExtensionConfig('doctrine'));
    }

    public function testPrependDoesNotRegisterTimelineMappingWhenAuditingAndTimelineAreDisabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $container->prependExtensionConfig($extension->getAlias(), [
            'toolbox' => [
                'auditing' => false,
                'activity' => false,
                'attachment' => false,
                'bookmark' => false,
                'follower' => false,
                'timeline' => false,
            ],
        ]);

        $extension->prepend($container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
    }

    public function testLoadRemovesAuditingServicesWhenAuditingIsDisabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new WebDeveloversResourceExtension();

        $extension->load([
            [
                'toolbox' => [
                    'auditing' => false,
                ],
            ],
        ], $container);

        self::assertTrue($container->hasDefinition(TimelineDiffPresenter::class));
        self::assertTrue($container->hasDefinition(TimelineValueFormatterRegistry::class));
        self::assertTrue($container->hasDefinition(DoctrineRelationValueFormatter::class));
        self::assertTrue($container->hasDefinition(IsoDateTimeValueFormatter::class));
        self::assertTrue($container->hasDefinition(MoneyValueFormatter::class));
        self::assertTrue($container->hasDefinition(ScalarValueFormatter::class));
    }
}
