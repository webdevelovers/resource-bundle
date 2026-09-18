<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Factory;

use LogicException;
use ReflectionClass;
use WebDevelovers\ResourceBundle\Attribute\AsResourceIndex;
use WebDevelovers\ResourceBundle\Index\Builder\IndexBuilder;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

use function method_exists;
use function sprintf;

final class ResourceIndexDefinitionFactory
{
    public function create(object $index): IndexDefinitionInterface
    {
        $reflection = new ReflectionClass($index);
        $attribute = $this->getAttribute($reflection);

        $builder = new IndexBuilder(
            name: $attribute->name,
            resourceClass: $attribute->resourceClass,
            provider: $attribute->provider,
        );

        if (! method_exists($index, $attribute->buildMethod)) {
            throw new LogicException(sprintf(
                'The configured build method "%s" does not exist on index "%s".',
                $attribute->buildMethod,
                $reflection->getName(),
            ));
        }

        $index->{$attribute->buildMethod}($builder);

        return $builder->getDefinition();
    }

    private function getAttribute(ReflectionClass $reflection): AsResourceIndex
    {
        $attributes = $reflection->getAttributes(AsResourceIndex::class);
        $attribute = $attributes[0] ?? null;

        if ($attribute === null) {
            throw new LogicException(sprintf(
                'Index class "%s" must be configured with #[%s].',
                $reflection->getName(),
                AsResourceIndex::class,
            ));
        }

        return $attribute->newInstance();
    }
}
