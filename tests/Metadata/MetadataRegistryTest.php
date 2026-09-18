<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Metadata;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistry;

final class MetadataRegistryTest extends TestCase
{
    public function testAddAndGet(): void
    {
        $registry = new MetadataRegistry();
        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.product');

        $registry->add($metadata);

        self::assertSame($metadata, $registry->get('app.product'));
        self::assertSame(['app.product' => $metadata], $registry->getAll());
    }

    public function testGetNonExistingThrowsException(): void
    {
        $registry = new MetadataRegistry();

        $this->expectException(InvalidArgumentException::class);
        $registry->get('foo');
    }

    public function testGetByClass(): void
    {
        $registry = new MetadataRegistry();
        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.product');
        $metadata->expects(self::once())->method('hasClass')->with('model')->willReturn(true);
        $metadata->expects(self::once())->method('getClass')->with('model')->willReturn('App\Entity\Product');

        $registry->add($metadata);

        self::assertSame($metadata, $registry->getByClass('App\Entity\Product'));
    }

    public function testGetByClassWithProxyClassName(): void
    {
        $registry = new MetadataRegistry();
        $metadata = $this->createMock(MetadataInterface::class);
        $metadata->method('getAlias')->willReturn('app.product');
        $metadata->expects(self::once())->method('hasClass')->with('model')->willReturn(true);
        $metadata->expects(self::once())->method('getClass')->with('model')->willReturn('App\Entity\Product');

        $registry->add($metadata);

        self::assertSame($metadata, $registry->getByClass('Proxies\__CG__\App\Entity\Product'));
    }

    public function testGetByClassNonExistingThrowsException(): void
    {
        $registry = new MetadataRegistry();

        $this->expectException(InvalidArgumentException::class);
        $registry->getByClass('App\Entity\NonExisting');
    }

    public function testAddFromAliasAndConfiguration(): void
    {
        $registry = new MetadataRegistry();
        $registry->addFromAliasAndConfiguration('app.product', ['foo' => 'bar']);

        $metadata = $registry->get('app.product');
        self::assertSame('product', $metadata->name);
        self::assertSame('bar', $metadata->getParameter('foo'));
    }
}
