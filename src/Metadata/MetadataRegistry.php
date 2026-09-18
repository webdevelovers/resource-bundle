<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Metadata;

use InvalidArgumentException;

use WebDevelovers\ResourceBundle\ClassUtils;
use function sprintf;

final class MetadataRegistry implements MetadataRegistryInterface
{
    /** @var MetadataInterface[] */
    private array $metadata = [];

    /** @var array<string, MetadataInterface> */
    private array $metadataByClass = [];

    /** @return MetadataInterface[]  */
    public function getAll(): array
    {
        return $this->metadata;
    }

    public function get(string $alias): MetadataInterface
    {
        return $this->metadata[$alias] ?? throw new InvalidArgumentException(
            sprintf('Resource "%s" does not exist.', $alias),
        );
    }

    /** @throws InvalidArgumentException */
    public function getByClass(string $className): MetadataInterface
    {
        $className = ClassUtils::getRealClassName($className);

        return $this->metadataByClass[$className] ?? throw new InvalidArgumentException(
            sprintf('Resource with model class "%s" does not exist.', $className),
        );
    }

    public function add(MetadataInterface $metadata): void
    {
        $this->metadata[$metadata->getAlias()] = $metadata;

        if ($metadata->hasClass('model')) {
            $this->metadataByClass[ClassUtils::getRealClassName($metadata->getClass('model'))] = $metadata;
        }
    }

    /** @param array<string,mixed> $configuration */
    public function addFromAliasAndConfiguration(string $alias, array $configuration): void
    {
        $this->add(Metadata::fromAliasAndConfiguration($alias, $configuration));
    }
}
