<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Metadata;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Metadata\Metadata;

final class MetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $parameters = [
            'templates' => 'admin/product',
            'classes' => [
                'model' => 'App\Entity\Product',
                'controller' => [
                    'index' => 'App\Controller\IndexAction',
                ],
            ],
        ];
        $metadata = Metadata::fromAliasAndConfiguration('app.product', $parameters);

        self::assertSame('product', $metadata->name);
        self::assertSame('app', $metadata->applicationName);
        self::assertSame('app.product', $metadata->getAlias());
        self::assertSame('admin/product', $metadata->templatesNamespace);
        self::assertSame('doctrine/orm', $metadata->driver);
        self::assertSame($parameters, $metadata->parameters);
    }

    public function testGetHumanizedName(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.MyProductResource', []);
        self::assertSame('my product resource', $metadata->getHumanizedName());

        $metadataUnder = Metadata::fromAliasAndConfiguration('app.my_product_resource', []);
        self::assertSame('my product resource', $metadataUnder->getHumanizedName());
    }

    public function testGetPluralName(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        self::assertSame('products', $metadata->getPluralName());
    }

    public function testGetParameter(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', ['foo' => 'bar']);
        self::assertSame('bar', $metadata->getParameter('foo'));
        self::assertTrue($metadata->hasParameter('foo'));
        self::assertFalse($metadata->hasParameter('baz'));

        $this->expectException(InvalidArgumentException::class);
        $metadata->getParameter('baz');
    }

    public function testGetClass(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', ['classes' => ['model' => 'App\Entity\Product']]);
        self::assertSame('App\Entity\Product', $metadata->getClass('model'));
        self::assertTrue($metadata->hasClass('model'));
        self::assertFalse($metadata->hasClass('controller'));

        $this->expectException(InvalidArgumentException::class);
        $metadata->getClass('controller');
    }

    public function testGetAction(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'classes' => [
                'controller' => ['index' => 'App\Action\Index'],
            ],
        ]);
        self::assertSame('App\Action\Index', $metadata->getAction('index'));
        self::assertTrue($metadata->hasAction('index'));
        self::assertFalse($metadata->hasAction('create'));

        $this->expectException(InvalidArgumentException::class);
        $metadata->getAction('create');
    }

    public function testGetServiceId(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        self::assertSame('app.repository.product', $metadata->getServiceId('repository'));
        self::assertSame('app.repository.product.inner', $metadata->getServiceId('repository', 'inner'));
    }

    public function testGetPermissionCode(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        self::assertSame('app.product.index', $metadata->getPermissionCode('index'));
    }

    public function testInvalidAliasThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Metadata::fromAliasAndConfiguration('product', []);
    }
}
