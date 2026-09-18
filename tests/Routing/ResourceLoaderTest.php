<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\Routing\ResourceLoader;
use WebDevelovers\ResourceBundle\Routing\RouteFactoryInterface;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ResourceLoaderTest extends TestCase
{
    public function testSupports(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);
        $loader = new ResourceLoader($registry, $factory);

        self::assertTrue($loader->supports('any', 'wd.resource'));
        self::assertFalse($loader->supports('any', 'yaml'));
    }

    public function testLoadWithTaggedResourceConfiguration(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);

        $registry->expects(self::once())
            ->method('get')
            ->with('app.product')
            ->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory, [[
            'alias' => 'app.product',
            'only' => ['index'],
        ]]);

        $result = $loader->load('@wd.resource.tagged');

        self::assertSame($collection, $result);
        self::assertCount(1, $result);
        self::assertNotNull($result->get('app_product_index'));
    }

    public function testLoadWithBasicConfiguration(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);

        $registry->expects(self::once())
            ->method('get')
            ->with('app.product')
            ->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
YAML;

        $result = $loader->load($resourceYaml);

        self::assertSame($collection, $result);
        // By default it should generate 6 routes: show, index, create, update, delete, bulkDelete
        self::assertCount(6, $result);
        self::assertNotNull($result->get('app_product_index'));
    }

    public function testLoadWithOnlyOption(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
only: [index, show]
YAML;

        $loader->load($resourceYaml);

        self::assertCount(2, $collection);
        self::assertNotNull($collection->get('app_product_index'));
        self::assertNotNull($collection->get('app_product_show'));
    }

    public function testLoadWithExceptOption(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
except: [delete, bulkDelete]
YAML;

        $loader->load($resourceYaml);

        // 6 default - 2 except = 4
        self::assertCount(4, $collection);
        self::assertNull($collection->get('app_product_delete'));
    }

    public function testLoadWithPhpConfigurationFile(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory);

        $resourcePhpFile = tempnam(sys_get_temp_dir(), 'wd_resource_loader_');
        self::assertNotFalse($resourcePhpFile);

        file_put_contents(
            $resourcePhpFile,
            <<<'PHP'
<?php

return [
    'alias' => 'app.product',
    'only' => ['index'],
];
PHP,
        );

        $phpConfigPath = $resourcePhpFile . '.php';
        rename($resourcePhpFile, $phpConfigPath);

        try {
            $loader->load($phpConfigPath);
            self::assertCount(1, $collection);
            self::assertNotNull($collection->get('app_product_index'));
        } finally {
            if (is_file($phpConfigPath)) {
                unlink($phpConfigPath);
            }
        }
    }

    public function testLoadWithFullConfiguration(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'classes' => ['model' => 'App\Entity\Product'],
        ]);
        $registry->method('get')->willReturn($metadata);

        $collection = new RouteCollection();
        $factory->method('createRouteCollection')->willReturn($collection);

        // Catturiamo i parametri passati a createRoute per verificarli
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
path: catalogo
identifier: slug
section: admin
route_name_prefix: web
templates: 'admin/product'
redirect: index
vars:
    all:
        foo: bar
    index:
        baz: qux
YAML;

        $result = $loader->load($resourceYaml);

        // Verifica Index Route
        $indexRoute = $result->get('app_admin_product_web_index');
        self::assertNotNull($indexRoute);
        self::assertSame('/catalogo/', $indexRoute->getPath());
        self::assertSame('admin/product/index.html.twig', $indexRoute->getDefault('_wd')['template']);
        self::assertSame('admin', $indexRoute->getDefault('_wd')['section']);
        self::assertSame('web', $indexRoute->getDefault('_wd')['route_name_prefix']);
        self::assertSame('bar', $indexRoute->getDefault('_wd')['vars']['foo']);
        self::assertSame('qux', $indexRoute->getDefault('_wd')['vars']['baz']);

        // Verifica Create Route
        $createRoute = $result->get('app_admin_product_web_create');
        self::assertNotNull($createRoute);
        self::assertSame('/catalogo/new', $createRoute->getPath());
        self::assertSame('app_admin_product_web_index', $createRoute->getDefault('_wd')['redirect']);

        // Verifica Update Route
        $updateRoute = $result->get('app_admin_product_web_update');
        self::assertNotNull($updateRoute);
        self::assertSame('/catalogo/{slug}/edit', $updateRoute->getPath());
        self::assertSame('[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[4-7][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}', $updateRoute->getRequirement('slug'));
    }

    public function testLoadWithUuidIdentifierType(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturn(new RouteCollection());
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
identifier: uuid
identifier_type: uuid
only: [show]
YAML;

        $result = $loader->load($resourceYaml);
        $route = $result->get('app_product_show');

        self::assertNotNull($route);
        self::assertSame('/products/{uuid}', $route->getPath());
        self::assertSame('[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[4-7][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}', $route->getRequirement('uuid'));
    }

    public function testLoadThrowsExceptionOnInvalidIdentifierType(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
identifier_type: foo
YAML;

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('routing.identifier_type');

        $loader->load($resourceYaml);
    }

    public function testLoadWithCustomPermissionString(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturn(new RouteCollection());
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
permission: product.manage
only: [show]
YAML;

        $result = $loader->load($resourceYaml);
        $route = $result->get('app_product_show');

        self::assertNotNull($route);
        self::assertSame('product.manage', $route->getDefault('_wd')['permission']);
    }

    public function testLoadThrowsExceptionOnInvalidPermissionType(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
permission: [invalid]
YAML;

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('routing.permission');

        $loader->load($resourceYaml);
    }

    public function testLoadWithTemplatesColon(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturn(new RouteCollection());
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
templates: 'App:Product'
only: [index]
YAML;

        $result = $loader->load($resourceYaml);
        $route = $result->get('app_product_index');
        self::assertNotNull($route);
        self::assertSame('App:Product:index.html.twig', $route->getDefault('_wd')['template']);
    }

    public function testLoadWithDottedRouteNamePrefix(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturn(new RouteCollection());
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
route_name_prefix: web.api
only: [index]
YAML;

        $result = $loader->load($resourceYaml);

        self::assertNotNull($result->get('app_product_web_api_index'));
    }

    public function testLoadBulkDeleteSpecialConfiguration(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturn(new RouteCollection());
        $factory->method('createRoute')
            ->willReturnCallback(function ($path, $defaults, $requirements, $options, $host, $schemes, $methods) {
                return new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods);
            });

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
only: [bulkDelete]
YAML;

        $result = $loader->load($resourceYaml);
        $route = $result->get('app_product_bulk_delete');

        self::assertNotNull($route);
        self::assertFalse($route->getDefault('_wd')['paginate']);
        self::assertSame('findById', $route->getDefault('_wd')['repository']['method']);
        self::assertSame(['$ids'], $route->getDefault('_wd')['repository']['arguments']);
    }

    public function testLoadThrowsExceptionOnBothOnlyAndExcept(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $loader = new ResourceLoader($registry, $factory);

        $resourceYaml = <<<YAML
alias: app.product
only: [index]
except: [show]
YAML;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can configure only one of "except" & "only" options.');

        $loader->load($resourceYaml);
    }

    public function testResolverAccessors(): void
    {
        $registry = $this->createStub(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);
        $loader = new ResourceLoader($registry, $factory);

        $resolver = $this->createStub(\Symfony\Component\Config\Loader\LoaderResolverInterface::class);
        $loader->setResolver($resolver);
        self::assertSame($resolver, $loader->getResolver());
    }

    public function testLoadWithMultipleConfigurationsInTaggedResources(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->with('app.product')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturnCallback(static fn () => new RouteCollection());
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory, [
            [
                [
                    'alias' => 'app.product',
                    'only' => ['index', 'show'],
                    'section' => 'public',
                ],
                [
                    'alias' => 'app.product',
                    'only' => ['create', 'update'],
                    'section' => 'admin',
                ],
            ],
        ]);

        $result = $loader->load('@wd.resource.tagged');

        self::assertCount(4, $result);
        self::assertNotNull($result->get('app_public_product_index'));
        self::assertNotNull($result->get('app_public_product_show'));
        self::assertNotNull($result->get('app_admin_product_create'));
        self::assertNotNull($result->get('app_admin_product_update'));
    }

    public function testLoadWithMultipleConfigurationsInPhpFile(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
        $factory = $this->createStub(RouteFactoryInterface::class);

        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $registry->method('get')->with('app.product')->willReturn($metadata);

        $factory->method('createRouteCollection')->willReturnCallback(static fn () => new RouteCollection());
        $factory->method('createRoute')->willReturn(new Route('/'));

        $loader = new ResourceLoader($registry, $factory);

        $tmpFile = tempnam(sys_get_temp_dir(), 'wd_multi_route_') . '.php';
        file_put_contents($tmpFile, <<<'PHP'
<?php
return [
    [
        'alias' => 'app.product',
        'only' => ['index'],
        'section' => 'public',
    ],
    [
        'alias' => 'app.product',
        'only' => ['create'],
        'section' => 'admin',
    ],
];
PHP);

        try {
            $result = $loader->load($tmpFile, 'wd.resource');

            self::assertCount(2, $result);
            self::assertNotNull($result->get('app_public_product_index'));
            self::assertNotNull($result->get('app_admin_product_create'));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testSlugify(): void
    {
        self::assertSame('my-resource-name', ResourceLoader::slugify('My Resource Name'));
        self::assertSame('my_resource_name', ResourceLoader::slugify('My Resource Name', '_'));
        self::assertSame('', ResourceLoader::slugify(null));
        self::assertSame('', ResourceLoader::slugify(''));
    }
}
