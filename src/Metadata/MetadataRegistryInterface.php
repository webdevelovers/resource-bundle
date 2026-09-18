<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Metadata;

use InvalidArgumentException;

/** Interface for the registry of all resources.*/
interface MetadataRegistryInterface
{
    /** @return MetadataInterface[] */
    public function getAll(): array;

    /** @throws InvalidArgumentException */
    public function get(string $alias): MetadataInterface;

    /** @throws InvalidArgumentException */
    public function getByClass(string $className): MetadataInterface;

    public function add(MetadataInterface $metadata): void;

    /** @param array<string,mixed> $configuration */
    public function addFromAliasAndConfiguration(string $alias, array $configuration): void;
}
