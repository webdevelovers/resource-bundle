<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelinePayloadExtractor;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelineSubscriber;
use WebDevelovers\ResourceBundle\Audit\DoctrineValueNormalizer;

final class WebDeveloversResourceExtension extends Extension implements PrependExtensionInterface
{
    private const array TOOLBOX_FEATURES = [
        'activity',
        'attachment',
        'bookmark',
        'follower',
        'timeline',
    ];

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $processedConfig = $this->processConfiguration(new Configuration(), $configs);
        $toolboxConfig = $processedConfig['toolbox'];
        if ($toolboxConfig['auditing'] === true) {
            $toolboxConfig['timeline'] = true;
        }

        $templatesPath = (string) realpath(__DIR__ . '/../../templates');

        $container->prependExtensionConfig('twig', [
            'paths' => [
                $templatesPath => 'WebDeveloversResource',
            ],
        ]);

        if ($this->hasEnabledToolboxFeature($toolboxConfig)) {
            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'mappings' => [
                        'Toolbox' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => (string) realpath(__DIR__ . '/../../src/Toolbox/Entity'),
                            'prefix' => 'WebDevelovers\\ResourceBundle\\Toolbox\\Entity',
                            'alias' => 'Toolbox',
                        ],
                    ],
                ],
            ]);
        }
    }

    /** @param array<string, bool> $toolboxConfig */
    private function hasEnabledToolboxFeature(array $toolboxConfig): bool
    {
        foreach (self::TOOLBOX_FEATURES as $feature) {
            if (($toolboxConfig[$feature] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processedConfig = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        if ($processedConfig['toolbox']['auditing'] !== true) {
            $container->removeDefinition(DoctrineTimelineSubscriber::class);
            $container->removeDefinition(DoctrineTimelinePayloadExtractor::class);
            $container->removeDefinition(DoctrineValueNormalizer::class);
        }
    }
}

