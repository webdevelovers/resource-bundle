<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\ObjectMapper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use WebDevelovers\ResourceBundle\ResourceInterface;
use function class_exists;
use function is_string;
use function method_exists;

final class WDDTOMapper implements DTOMapperInterface
{
    /** @var list<string> */
    private const array RESOURCE_FIELDS_TO_IGNORE = ['id', 'createdAt', 'updatedAt'];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function mapDTOToResource(object $dto, string|ResourceInterface $resource): ResourceInterface
    {
        if (method_exists($dto, 'toEntity')) {
            return $dto->toEntity($resource, $this->entityManager);
        }

        throw new \RuntimeException('DTO does not implement toEntity method');

        $target = is_string($resource) ? new $resource() : $resource;
        $accessor = PropertyAccess::createPropertyAccessor();
        $ignoredValues = [];

        foreach (self::RESOURCE_FIELDS_TO_IGNORE as $field) {
            if ($accessor->isReadable($target, $field)) {
                $ignoredValues[$field] = $accessor->getValue($target, $field);
            }
        }

        foreach ($ignoredValues as $field => $value) {
            if ($accessor->isWritable($mapped, $field)) {
                $accessor->setValue($mapped, $field, $value);
            }
        }

        return $mapped;
    }

    public function mapResourceToDTO(ResourceInterface $resource, string|object $dto): object
    {
        if (is_string($dto) && class_exists($dto)) {
            $dto = new $dto();
        }

        if (method_exists($dto, 'fromEntity')) {
            return $dto->fromEntity($resource, $this->entityManager);
        }

        throw new \RuntimeException('DTO does not implement fromEntity method');

        return $this->fallbackMapper->mapResourceToDTO($resource, $dto);
    }
}
