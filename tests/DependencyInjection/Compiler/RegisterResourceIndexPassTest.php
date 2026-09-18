<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use WebDevelovers\ResourceBundle\Attribute\AsResourceIndex;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceIndexPass;

#[AsResourceIndex(name: 'wd.test_index')]
final class TaggedIndexForCompilerPass
{
}

final class UntaggedIndexForCompilerPass
{
}

final class RegisterResourceIndexPassTest extends TestCase
{
    public function testProcessTagsClassesWithAsResourceIndexAttribute(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'app.index.tagged',
            new Definition(TaggedIndexForCompilerPass::class),
        );

        $pass = new RegisterResourceIndexPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('app.index.tagged'));
        self::assertSame([
            [],
        ], $container->getDefinition('app.index.tagged')->getTag('app.resource.index'));
    }

    public function testProcessSkipsClassesWithoutAsResourceIndexAttribute(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'app.index.untagged',
            new Definition(UntaggedIndexForCompilerPass::class),
        );

        $pass = new RegisterResourceIndexPass();
        $pass->process($container);

        self::assertSame([], $container->getDefinition('app.index.untagged')->getTag('app.resource.index'));
    }
}
