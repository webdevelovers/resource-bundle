<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Registry;

use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Factory\ResourceIndexDefinitionFactory;

use function sprintf;

final class IndexRegistry implements IndexRegistryInterface
{
    /** @var array<string, IndexDefinitionInterface>|null */
    private array|null $definitions = null;

    /** @param iterable<object> $indexes */
    public function __construct(
        #[AutowireIterator('app.resource.index')]
        private readonly iterable $indexes,
        private readonly ResourceIndexDefinitionFactory $definitionFactory,
    ) {
    }

    public function get(string $name): IndexDefinitionInterface
    {
        $definitions = $this->getDefinitions();

        if (! isset($definitions[$name])) {
            throw new LogicException(sprintf('Index "%s" is not registered.', $name));
        }

        return $definitions[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->getDefinitions()[$name]);
    }

    /** @return array<string, IndexDefinitionInterface> */
    private function getDefinitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];

        foreach ($this->indexes as $index) {
            $definition = $this->definitionFactory->create($index);
            $name = $definition->getName();

            if (isset($definitions[$name])) {
                throw new LogicException(sprintf('Index "%s" is already registered.', $name));
            }

            $definitions[$name] = $definition;
        }

        return $this->definitions = $definitions;
    }
}
