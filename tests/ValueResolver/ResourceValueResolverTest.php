<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactoryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\ValueResolver\ResourceValueResolver;

final class DummyResource implements ResourceInterface
{
    public function __toString(): string
    {
        return 'dummy';
    }
}

class ResourceValueResolverTest extends TestCase
{
    private MetadataRegistryInterface $registry;
    private RequestConfigurationFactoryInterface $factory;
    private EntityManagerInterface $entityManager;
    private ResourceValueResolver $resolver;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(MetadataRegistryInterface::class);
        $this->factory = $this->createStub(RequestConfigurationFactoryInterface::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->resolver = new ResourceValueResolver($this->registry, $this->factory, $this->entityManager);
    }

    public function testResolveWithWrongArgumentType(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('resource', \stdClass::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testResolveWithoutResourceAliasThrowsException(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('resource', ResourceInterface::class, false, false, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to autowire resource route without a resource alias');

        $this->resolver->resolve($request, $argument);
    }

    public function testResolveNotFoundThrowsException(): void
    {
        $request = new Request(['id' => '123']);
        $request->attributes->set('_resource_alias', 'app.user');
        $request->attributes->set('id', '123');
        $argument = new ArgumentMetadata('resource', ResourceInterface::class, false, false, null);

        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getHumanizedName')->willReturn('user');
        $metadata->method('getClass')->willReturnCallback(fn ($v) => $v === 'model' ? 'App\Entity\User' : '');

        $config = $this->createStub(RequestConfiguration::class);
        $reflection = new \ReflectionProperty(RequestConfiguration::class, 'request');
        $reflection->setValue($config, $request);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturnCallback(fn ($v) => $v === '123' ? null : null);

        $this->registry->method('get')->willReturnCallback(fn ($v) => $v === 'app.user' ? $metadata : null);
        $this->factory->method('create')->willReturn($config);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The "user" has not been found');

        $this->resolver->resolve($request, $argument);
    }

    public function testResolveSuccessById(): void
    {
        $request = new Request();
        $request->attributes->set('_resource_alias', 'app.user');
        $request->attributes->set('id', '123');
        $argument = new ArgumentMetadata('resource', ResourceInterface::class, false, false, null);

        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getClass')->willReturnCallback(fn ($v) => $v === 'model' ? 'App\Entity\User' : '');

        $config = $this->createStub(RequestConfiguration::class);
        $reflection = new \ReflectionProperty(RequestConfiguration::class, 'request');
        $reflection->setValue($config, $request);

        $resource = $this->createStub(ResourceInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturnCallback(fn ($v) => $v === '123' ? $resource : null);

        $this->registry->method('get')->willReturnCallback(fn ($v) => $v === 'app.user' ? $metadata : null);
        $this->factory->method('create')->willReturn($config);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->resolver->resolve($request, $argument);
        $resolved = iterator_to_array($result);

        $this->assertCount(1, $resolved);
        $this->assertSame($resource, $resolved[0]);
    }

    public function testResolveSuccessByCriteria(): void
    {
        $request = new Request();
        $request->attributes->set('_resource_alias', 'app.user');
        $request->attributes->set('slug', 'john-doe');
        $argument = new ArgumentMetadata('resource', ResourceInterface::class, false, false, null);

        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getClass')->willReturnCallback(fn ($v) => $v === 'model' ? 'App\Entity\User' : '');

        $config = $this->createStub(RequestConfiguration::class);
        $reflection = new \ReflectionProperty(RequestConfiguration::class, 'request');
        $reflection->setValue($config, $request);
        $config->method('getCriteria')->willReturn(['enabled' => true]);

        $resource = $this->createStub(ResourceInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(function ($c) use ($resource) {
            if ($c === ['slug' => 'john-doe', 'enabled' => true]) {
                return $resource;
            }
            return null;
        });

        $this->registry->method('get')->willReturnCallback(fn ($v) => $v === 'app.user' ? $metadata : null);
        $this->factory->method('create')->willReturn($config);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $result = $this->resolver->resolve($request, $argument);
        $resolved = iterator_to_array($result);

        $this->assertCount(1, $resolved);
        $this->assertSame($resource, $resolved[0]);
    }

    public function testResolveWithConcreteResourceType(): void
    {
        $request = new Request();
        $request->attributes->set('_resource_alias', 'app.user');
        $request->attributes->set('id', '123');
        $argument = new ArgumentMetadata('resource', DummyResource::class, false, false, null);

        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getClass')->willReturnCallback(fn ($v) => $v === 'model' ? 'App\Entity\User' : '');

        $config = $this->createStub(RequestConfiguration::class);
        $reflection = new \ReflectionProperty(RequestConfiguration::class, 'request');
        $reflection->setValue($config, $request);

        $resource = new DummyResource();
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturnCallback(fn ($v) => $v === '123' ? $resource : null);

        $this->registry->method('get')->willReturn($metadata);
        $this->factory->method('create')->willReturn($config);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $resolved = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $resolved);
        $this->assertSame($resource, $resolved[0]);
    }
}
