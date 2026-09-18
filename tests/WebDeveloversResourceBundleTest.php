<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceActionPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceIndexPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceRoutesPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourcesPass;
use WebDevelovers\ResourceBundle\WebDeveloversResourceBundle;

final class WebDeveloversResourceBundleTest extends TestCase
{
    public function testBuildRegistersExpectedCompilerPasses(): void
    {
        $bundle = new WebDeveloversResourceBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);

        $passClasses = array_map(
            static fn (object $pass): string => $pass::class,
            $container->getCompilerPassConfig()->getPasses(),
        );

        self::assertContains(RegisterResourcesPass::class, $passClasses);
        self::assertContains(RegisterResourceActionPass::class, $passClasses);
        self::assertContains(RegisterResourceIndexPass::class, $passClasses);
        self::assertContains(RegisterResourceRoutesPass::class, $passClasses);
    }
}
