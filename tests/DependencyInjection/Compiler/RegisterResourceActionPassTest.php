<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebDevelovers\ResourceBundle\CRUD\Show;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceActionPass;

final class RegisterResourceActionPassTest extends TestCase
{
    public function testProcessRegistersConfiguredResourceActionsAndSkipsMissingDefaults(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('wd.resources', [
            'app.product' => [
                'classes' => [
                    'model' => 'App\\Entity\\Product',
                    'controller' => [
                        'show' => Show::class,
                    ],
                ],
            ],
        ]);

        $pass = new RegisterResourceActionPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('wd.resource.action.default_show'));
        self::assertTrue($container->hasDefinition('wd.resource.action.default_index'));

        self::assertTrue($container->hasDefinition('app.resource_action.product.show'));
        self::assertTrue($container->hasParameter('app.action.product.show.class'));
        self::assertFalse($container->hasParameter('app.action.product.index.class'));
    }

    public function testProcessDoesNothingWhenResourcesParameterIsMissing(): void
    {
        $container = new ContainerBuilder();

        $pass = new RegisterResourceActionPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('wd.resource.action.default_show'));
    }
}
