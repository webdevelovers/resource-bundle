<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use InvalidArgumentException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;

use function array_diff;
use function array_is_list;
use function array_merge;
use function in_array;
use function is_file;
use function is_string;
use function is_array;
use function str_ends_with;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function trim;

final class ResourceLoader implements LoaderInterface
{
    private const string TAGGED_RESOURCE = '@wd.resource.tagged';

    /** @var list<string> */
    private const array DEFAULT_ROUTES_TO_GENERATE = ['show', 'index', 'create', 'update', 'delete', 'bulkDelete'];

    private PropertyAccessor $propertyAccessor;
    private LoaderResolverInterface $resolver;

    public function __construct(
        private readonly MetadataRegistryInterface $resourceRegistry,
        private readonly RouteFactoryInterface $routeFactory,
        private readonly array $taggedResourceConfigurations = [],
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function load(mixed $resource, string|null $type = null): RouteCollection
    {
        if ($resource === self::TAGGED_RESOURCE || $resource === null || $resource === '') {
            return $this->loadTaggedResources();
        }

        $resolved = $this->resolveConfiguration($resource);

        if (array_is_list($resolved) && isset($resolved[0]) && is_array($resolved[0])) {
            $routes = $this->routeFactory->createRouteCollection();
            foreach ($resolved as $configuration) {
                if (is_array($configuration)) {
                    $routes->addCollection($this->loadConfiguration($configuration));
                }
            }

            return $routes;
        }

        return $this->loadConfiguration($resolved);
    }

    private function loadTaggedResources(): RouteCollection
    {
        $routes = $this->routeFactory->createRouteCollection();

        foreach ($this->taggedResourceConfigurations as $configuration) {
            if (! is_array($configuration)) {
                continue;
            }

            if (array_is_list($configuration) && isset($configuration[0]) && is_array($configuration[0])) {
                foreach ($configuration as $subConfig) {
                    if (is_array($subConfig)) {
                        $routes->addCollection($this->loadConfiguration($subConfig));
                    }
                }
                continue;
            }

            $configurationRoutes = $this->loadConfiguration($configuration);
            $routes->addCollection($configurationRoutes);
        }

        return $routes;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function loadConfiguration(array $configuration): RouteCollection
    {
        $processor = new Processor();
        $configurationDefinition = new Configuration();
        $configuration = $processor->processConfiguration($configurationDefinition, ['routing' => $configuration]);

        if (! empty($configuration['only']) && ! empty($configuration['except'])) {
            throw new InvalidArgumentException('You can configure only one of "except" & "only" options.');
        }

        $routesToGenerate = self::DEFAULT_ROUTES_TO_GENERATE;
        if (! empty($configuration['only']) && is_array($configuration['only'])) {
            $routesToGenerate = $configuration['only'];
        } elseif (! empty($configuration['except']) && is_array($configuration['except'])) {
            $routesToGenerate = array_values(array_diff($routesToGenerate, $configuration['except']));
        }

        $metadata = $this->resourceRegistry->get($configuration['alias']);
        $routes = $this->routeFactory->createRouteCollection();

        $rootPath = sprintf('/%s/', $configuration['path'] ?? self::slugify($metadata->getPluralName()));
        $identifierName = $configuration['identifier'];
        $identifier = sprintf('{%s}', $identifierName);
        $identifierRequirement = $this->resolveIdentifierRequirement($configuration['identifier_type']);

        $routeDefinitions = [
            'index' => [$rootPath, 'index', ['GET'], 'index', []],
            'create' => [$rootPath . 'new', 'create', ['GET', 'POST'], 'create', []],
            'update' => [$rootPath . $identifier . '/edit', 'update', ['GET', 'POST'], 'update', [$identifierName => $identifierRequirement]],
            'show' => [$rootPath . $identifier, 'show', ['GET'], 'show', [$identifierName => $identifierRequirement]],
            'bulkDelete' => [$rootPath . 'bulk-delete', 'bulkDelete', ['DELETE'], 'bulk_delete', []],
            'delete' => [$rootPath . $identifier, 'delete', ['DELETE'], 'delete', [$identifierName => $identifierRequirement]],
        ];

        foreach ($routesToGenerate as $routeToGenerate) {
            if (! isset($routeDefinitions[$routeToGenerate])) {
                continue;
            }

            [$path, $actionName, $methods, $routeNameAction, $requirements] = $routeDefinitions[$routeToGenerate];
            $route = $this->createRoute(
                metadata: $metadata,
                configuration: $configuration,
                path: $path,
                actionName: $actionName,
                methods: $methods,
                requirements: $requirements,
            );

            $routes->add($this->getRouteName($metadata, $configuration, $routeNameAction), $route);
        }

        return $routes;
    }

    /** @return array<string, mixed> */
    private function resolveConfiguration(mixed $resource): array
    {
        if (is_array($resource)) {
            return $resource;
        }

        if (! is_string($resource)) {
            throw new InvalidArgumentException('The routing resource must be a YAML string, PHP file path or array.');
        }

        if (str_ends_with($resource, '.php') && is_file($resource)) {
            $configuration = require $resource;

            if (! is_array($configuration)) {
                throw new InvalidArgumentException('The PHP routing resource must return an array.');
            }

            return $configuration;
        }

        return Yaml::parse($resource);
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return $type === 'wd.resource';
    }

    /**
     * @param array<string,mixed> $configuration
     * @param array<int,string> $methods
     */
    private function createRoute(
        MetadataInterface $metadata,
        array $configuration,
        string $path,
        string $actionName,
        array $methods,
        array $requirements,
    ): Route {
        $defaults = [
            '_controller' => $metadata->getServiceId('resource_action', suffix: $actionName),
            '_resource_alias' => $metadata->getAlias(),
            '_wd' => [],
        ];

        $this->setDefault($defaults, '[_wd][section]', $configuration, '[section]', $actionName);
        $this->setDefault($defaults, '[_wd][route_name_prefix]', $configuration, '[route_name_prefix]', $actionName);
        $this->setDefault($defaults, '[_wd][input]', $configuration, '[input]', $actionName, ['create', 'update']);
        $this->setDefault($defaults, '[_wd][output]', $configuration, '[output]', $actionName, ['index', 'show']);
        $this->setDefault($defaults, '[_wd][message]', $configuration, '[message]', $actionName, ['create', 'update']);
        $this->setDefault($defaults, '[_wd][criteria]', $configuration, '[criteria]', $actionName);
        $this->setDefault($defaults, '[_wd][filterable]', $configuration, '[filterable]', $actionName);
        $this->setDefault($defaults, '[_wd][permission]', $configuration, '[permission]', $actionName);

        if (isset($configuration['vars']['all'])) {
            $defaults['_wd']['vars'] = $configuration['vars']['all'];
        }

        $this->setDefault($defaults, '[_wd][index]', $configuration, '[index]', $actionName, ['index']);
        $this->setDefault($defaults, '[_wd][form]', $configuration, '[form]', $actionName, ['create', 'update']);

        if (isset($configuration['templates']) && in_array($actionName, ['show', 'index', 'create', 'update'], true)) {
            $defaults['_wd']['template'] = sprintf(
                ! str_contains($configuration['templates'], ':') ? '%s/%s.html.twig' : '%s:%s.html.twig',
                $configuration['templates'],
                $actionName,
            );
        }

        if (isset($configuration['redirect']) && in_array($actionName, ['create', 'update'], true)) {
            $defaults['_wd']['redirect'] = $this->getRouteName($metadata, $configuration, $configuration['redirect']);
        }

        if (isset($configuration['vars'][$actionName])) {
            $vars = $defaults['_wd']['vars'] ?? [];
            $defaults['_wd']['vars'] = array_merge($vars, $configuration['vars'][$actionName]);
        }

        if ($actionName === 'bulkDelete') {
            $defaults['_wd']['paginate'] = false;
            $defaults['_wd']['repository'] = [
                'method' => 'findById',
                'arguments' => ['$ids'],
            ];
        }

        return $this->routeFactory->createRoute($path, $defaults, $requirements, [], '', [], $methods);
    }

    private function resolveIdentifierRequirement(string $identifierType): string
    {
        return match (strtolower($identifierType)) {
            'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[4-7][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}',
            default => '\\d+',
        };
    }

    /**
     * @param array<mixed> $default
     * @param array<string, mixed> $configuration
     * @param array<int,string> $appliedActions
     */
    private function setDefault(
        array &$default,
        string $defaultPath,
        array $configuration,
        string $configurationPath,
        string $action,
        array $appliedActions = [],
    ): void {
        if (! empty($appliedActions) && ! in_array($action, $appliedActions, true)) {
            return;
        }

        $configValue = $this->propertyAccessor->getValue($configuration, $configurationPath);

        if ($configValue === null) {
            return;
        }

        $this->propertyAccessor->setValue($default, $defaultPath, $configValue);
    }

    /** @param array<string,mixed> $configuration */
    private function getRouteName(MetadataInterface $metadata, array $configuration, string $actionName): string
    {
        $routeNamePrefix = isset($configuration['route_name_prefix'])
            ? str_replace(['.', '-'], '_', $configuration['route_name_prefix'])
            : null;

        return RouteFactory::generateRouteName(
            applicationName: $metadata->applicationName,
            resourceName: $metadata->name,
            routeName: $actionName,
            sectionName: $configuration['section'] ?? null,
            routeNamePrefix: $routeNamePrefix,
        );
    }

    public static function slugify(
        mixed $value,
        string $separator = '-',
        bool $toLowerCase = true,
    ): string {
        if (! is_string($value) || empty(trim($value))) {
            return '';
        }

        try {
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($value, $separator);

            return $toLowerCase ? $slug->lower()->toString() : $slug->toString();
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Error during slug generation: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->resolver;
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }
}
