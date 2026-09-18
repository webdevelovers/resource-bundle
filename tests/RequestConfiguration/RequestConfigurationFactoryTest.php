<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\RequestConfiguration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Controller\Parameters\ParametersParserInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactory;

final class RequestConfigurationFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $parser = $this->createMock(ParametersParserInterface::class);
        $registry = $this->createStub(MetadataRegistryInterface::class);

        $parser->expects(self::once())
            ->method('parseRequestValues')
            ->willReturnCallback(static fn (array $parameters): array => $parameters + ['parsed' => true]);

        $factory = new RequestConfigurationFactory($parser, $registry, defaultParameters: ['default' => 1]);

        $metadata = $this->createStub(MetadataInterface::class);
        $request = new Request();
        $request->attributes->set('_wd', ['section' => 'admin']);

        $config = $factory->create($metadata, $request);

        self::assertInstanceOf(RequestConfiguration::class, $config);
        self::assertSame('admin', $config->getSection());
        self::assertSame($metadata, $config->metadata);
        self::assertSame($request, $config->request);
        self::assertSame(1, $config->parameters->get('default'));
        self::assertTrue($config->parameters->get('parsed'));
    }

    public function testCreateSystemOperation(): void
    {
        $parser = $this->createStub(ParametersParserInterface::class);
        $registry = $this->createStub(MetadataRegistryInterface::class);

        $metadata = $this->createStub(MetadataInterface::class);
        $registry->method('get')->willReturn($metadata);

        $factory = new RequestConfigurationFactory($parser, $registry);

        $config = $factory->createSystemOperation('app.product', ['foo' => 'bar']);

        self::assertInstanceOf(RequestConfiguration::class, $config);
        self::assertSame($metadata, $config->metadata);
        self::assertNull($config->request);
        self::assertSame('bar', $config->parameters->get('foo'));
        self::assertSame('system', $config->parameters->get('context'));
        self::assertTrue($config->parameters->get('is_system_operation'));
    }

    public function testCreateSystemOperationAlwaysForcesSystemContext(): void
    {
        $parser = $this->createStub(ParametersParserInterface::class);
        $registry = $this->createStub(MetadataRegistryInterface::class);

        $metadata = $this->createStub(MetadataInterface::class);
        $registry->method('get')->willReturn($metadata);

        $factory = new RequestConfigurationFactory($parser, $registry);

        $config = $factory->createSystemOperation('app.product', [
            'context' => 'admin',
            'is_system_operation' => false,
        ]);

        self::assertSame('system', $config->parameters->get('context'));
        self::assertTrue($config->parameters->get('is_system_operation'));
    }
}
