<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\ObjectMapper;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\ObjectMapper\WDDTOMapper;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class WDDTOMapperTest extends TestCase
{
    public function testMapDTOToResourceUsesToEntityWhenAvailable(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $mapper = new WDDTOMapper($entityManager);
        $resource = new MapperTestResource();
        $dto = new MapperTestDTOWithToEntity('created-via-to-entity');

        $mapped = $mapper->mapDTOToResource($dto, $resource);

        self::assertSame('created-via-to-entity', $mapped->name);
    }

    public function testMapDTOToResourceUsesObjectMapperFallback(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $mapper = new WDDTOMapper($entityManager);
        $dto = new MapperPlainDTO('mapped-name', 99);

        $mapped = $mapper->mapDTOToResource($dto, MapperTestResource::class);

        self::assertSame('mapped-name', $mapped->name);
        self::assertSame(0, $mapped->id);
    }

    public function testMapResourceToDTOUsesFromEntityWhenAvailable(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $mapper = new WDDTOMapper($entityManager);
        $resource = new MapperTestResource();
        $resource->name = 'resource-name';

        $mapped = $mapper->mapResourceToDTO($resource, MapperTestDTOWithFromEntity::class);

        self::assertInstanceOf(MapperTestDTOWithFromEntity::class, $mapped);
        self::assertSame('resource-name', $mapped->name);
    }

    public function testMapResourceToDTOUsesObjectMapperFallback(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $mapper = new WDDTOMapper($entityManager);
        $resource = new MapperTestResource();
        $resource->name = 'fallback-name';

        $mapped = $mapper->mapResourceToDTO($resource, MapperPlainDTO::class);

        self::assertInstanceOf(MapperPlainDTO::class, $mapped);
        self::assertSame('fallback-name', $mapped->name);
    }
}

final class MapperTestResource implements ResourceInterface
{
    public int $id = 0;
    public string $name = '';

    public function __toString(): string
    {
        return 'mapper-test-resource';
    }
}

final class MapperPlainDTO
{
    public function __construct(
        public string $name = '',
        public int $id = 0,
    ) {
    }
}

final class MapperTestDTOWithToEntity
{
    public function __construct(private readonly string $name)
    {
    }

    public function toEntity(ResourceInterface|string $resource, EntityManagerInterface $entityManager): ResourceInterface
    {
        unset($entityManager);

        $entity = is_string($resource) ? new $resource() : $resource;
        $entity->name = $this->name;

        return $entity;
    }
}

final class MapperTestDTOWithFromEntity
{
    public string $name = '';

    public function fromEntity(ResourceInterface $resource, EntityManagerInterface $entityManager): self
    {
        unset($entityManager);

        $this->name = $resource->name;

        return $this;
    }
}
