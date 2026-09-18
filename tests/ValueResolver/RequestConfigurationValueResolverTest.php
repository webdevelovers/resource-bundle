<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\ValueResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactoryInterface;
use WebDevelovers\ResourceBundle\ValueResolver\RequestConfigurationValueResolver;

class RequestConfigurationValueResolverTest extends TestCase
{
    private MetadataRegistryInterface $registry;
    private RequestConfigurationFactoryInterface $factory;
    private RequestConfigurationValueResolver $resolver;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(MetadataRegistryInterface::class);
        $this->factory = $this->createStub(RequestConfigurationFactoryInterface::class);
        $this->resolver = new RequestConfigurationValueResolver($this->registry, $this->factory);
    }

    public function testResolveWithWrongArgumentType(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('config', \stdClass::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testResolveWithoutResourceAlias(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('config', RequestConfiguration::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testResolveSuccess(): void
    {
        $request = new Request();
        $request->attributes->set('_resource_alias', 'app.user');
        $argument = new ArgumentMetadata('config', RequestConfiguration::class, false, false, null);

        $metadata = $this->createStub(MetadataInterface::class);
        $config = $this->createStub(RequestConfiguration::class);

        $this->registry->method('get')
            ->willReturnCallback(function (string $alias) use ($metadata) {
                if ($alias === 'app.user') {
                    return $metadata;
                }
                throw new \InvalidArgumentException();
            });

        $this->factory->method('create')
            ->willReturnCallback(function ($m, $r) use ($metadata, $request, $config) {
                if ($m === $metadata && $r === $request) {
                    return $config;
                }
                throw new \InvalidArgumentException();
            });

        $result = $this->resolver->resolve($request, $argument);
        $resolved = iterator_to_array($result);

        $this->assertCount(1, $resolved);
        $this->assertSame($config, $resolved[0]);
    }
}
