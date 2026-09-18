<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\Routing\AutoResourceRouteLoader;
use WebDevelovers\ResourceBundle\Routing\ResourceLoader;
use WebDevelovers\ResourceBundle\Routing\RouteFactoryInterface;

final class AutoResourceRouteLoaderTest extends TestCase
{
    public function testSupportsDelegatesToInnerLoader(): void
    {
        $innerLoader = $this->createMock(LoaderInterface::class);
        $innerLoader->expects(self::once())
            ->method('supports')
            ->with('routes.yaml', 'yaml')
            ->willReturn(true);

        $resourceLoader = new ResourceLoader(
            $this->createStub(MetadataRegistryInterface::class),
            $this->createStub(RouteFactoryInterface::class),
        );

        $loader = new AutoResourceRouteLoader($innerLoader, $resourceLoader);

        self::assertTrue($loader->supports('routes.yaml', 'yaml'));
    }

    public function testResolverDelegation(): void
    {
        $innerLoader = $this->createMock(LoaderInterface::class);
        $resolver = $this->createStub(LoaderResolverInterface::class);

        $innerLoader->expects(self::once())->method('getResolver')->willReturn($resolver);
        $innerLoader->expects(self::once())->method('setResolver')->with($resolver);

        $resourceLoader = new ResourceLoader(
            $this->createStub(MetadataRegistryInterface::class),
            $this->createStub(RouteFactoryInterface::class),
        );

        $loader = new AutoResourceRouteLoader($innerLoader, $resourceLoader);

        self::assertSame($resolver, $loader->getResolver());
        $loader->setResolver($resolver);
    }

    public function testLoadAppendsTaggedRoutesOnce(): void
    {
        $innerLoader = $this->createMock(LoaderInterface::class);

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->with('app.product')->willReturn(
            Metadata::fromAliasAndConfiguration('app.product', []),
        );

        $factory = $this->createStub(RouteFactoryInterface::class);
        $factory->method('createRouteCollection')->willReturnCallback(static fn () => new RouteCollection());
        $factory->method('createRoute')->willReturn(new Route('/products/'));

        $resourceLoader = new ResourceLoader(
            $registry,
            $factory,
            [[
                'alias' => 'app.product',
                'only' => ['index'],
            ]],
        );

        $mainCollection = new RouteCollection();
        $mainCollection->add('app_homepage', new Route('/'));

        $innerLoader->expects(self::exactly(2))
            ->method('load')
            ->willReturnOnConsecutiveCalls($mainCollection, new RouteCollection());

        $loader = new AutoResourceRouteLoader($innerLoader, $resourceLoader);

        $firstResult = $loader->load('routes.yaml');
        self::assertSame($mainCollection, $firstResult);
        self::assertNotNull($firstResult->get('app_homepage'));
        self::assertNotNull($firstResult->get('app_product_index'));

        // Second load call should not re-load tagged resources.
        $secondResult = $loader->load('sub_routes.yaml');
        self::assertNull($secondResult->get('app_product_index'));
    }
}
