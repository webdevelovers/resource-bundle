<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use WebDevelovers\ResourceBundle\ResourceInterface;

use function array_key_exists;
use function get_object_vars;
use function is_a;
use function sprintf;

final readonly class PropertyAccessDTOMapper implements DTOMapperInterface
{
    public function mapDTOToResource(object $dto, string|ResourceInterface $resource): ResourceInterface
    {
        $resourceObject = is_string($resource) ? new $resource() : $resource;
        if (! $resourceObject instanceof ResourceInterface) {
            throw new \InvalidArgumentException(sprintf('Mapped object must implement "%s".', ResourceInterface::class));
        }

        $mapped = $this->copyProperties($dto, $resourceObject);

        if (! $mapped instanceof ResourceInterface) {
            throw new \InvalidArgumentException(sprintf('Mapped object must implement "%s".', ResourceInterface::class));
        }

        return $mapped;
    }

    public function mapResourceToDTO(ResourceInterface $resource, string $dtoClass): object
    {
        $dto = new $dtoClass();

        return $this->copyProperties($resource, $dto);
    }

    private function copyProperties(object $source, object $target): object
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach (get_object_vars($source) as $property => $value) {
            try {
                if ($accessor->isWritable($target, $property)) {
                    $accessor->setValue($target, $property, $value);
                    continue;
                }

                if (array_key_exists($property, get_object_vars($target))) {
                    $target->{$property} = $value;
                }
            } catch (ExceptionInterface) {
                continue;
            }
        }

        return $target;
    }
}
