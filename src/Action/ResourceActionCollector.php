<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Action;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use function is_array;
use function is_string;
use function usort;

final class ResourceActionCollector
{
    /** @var array<string, list<ResourceActionDescriptor>> */
    private array $cacheByResourceAlias = [];

    public function __construct(
        private readonly RouterInterface $router,
        #[AutowireLocator('wd.resource.resource_action_support')]
        private readonly ContainerInterface $supportsLocator,
    ) {
    }

    /** @return list<ResourceActionDescriptor> */
    public function forResourceAlias(
        string $resourceAlias,
        ResourceActionType|null $type = null,
    ): array {
        $this->warmUpIfNeeded();

        $all = $this->cacheByResourceAlias[$resourceAlias] ?? [];
        $out = [];

        foreach ($all as $descriptor) {
            if ($type !== null && $descriptor->type !== $type) {
                continue;
            }

            $out[] = $descriptor;
        }

        return $out;
    }

    /** @return list<ResourceActionDescriptor> */
    public function forResource(
        ResourceInterface $resource,
        RequestConfiguration $requestConfiguration,
        string $resourceAlias,
        ResourceActionType|null $type = null,
    ): array {
        $this->warmUpIfNeeded();

        $all = $this->cacheByResourceAlias[$resourceAlias] ?? [];
        $out = [];

        foreach ($all as $descriptor) {
            if ($type !== null && $descriptor->type !== $type) {
                continue;
            }

            if (! $this->isSupported($descriptor, $resource, $requestConfiguration)) {
                continue;
            }

            $out[] = $descriptor;
        }

        return $out;
    }

    private function warmUpIfNeeded(): void
    {
        if ($this->cacheByResourceAlias !== []) {
            return;
        }

        $byAlias = [];

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $defaults = $route->getDefaults();
            $resourceAlias = $defaults['_resource_alias'] ?? null;
            $meta = $defaults['_resource_action'] ?? null;

            if (! is_string($resourceAlias) || $resourceAlias === '') {
                continue;
            }

            if (! is_array($meta)) {
                continue;
            }

            $typeValue = $meta['type'] ?? null;
            if (! is_string($typeValue) || $typeValue === '') {
                continue;
            }

            $confirmMessage = isset($meta['confirmMessage']) && is_string($meta['confirmMessage'])
                ? $meta['confirmMessage']
                : null;

            $modalId = null;
            $modalInternalComponent = null;
            $modalContext = null;

            $modal = $meta['modal'] ?? null;
            if (is_array($modal)) {
                $modalId = isset($modal['id']) && is_string($modal['id']) && $modal['id'] !== ''
                    ? $modal['id']
                    : 'modal-' . (string) $routeName;

                $modalInternalComponent =
                    isset($modal['internalComponent']) &&
                    is_string($modal['internalComponent']) &&
                    $modal['internalComponent'] !== ''
                    ? $modal['internalComponent']
                    : null;

                $modalContext = isset($modal['context']) && is_array($modal['context'])
                    ? $modal['context']
                    : null;
            }

            $descriptor = new ResourceActionDescriptor(
                resourceAlias: $resourceAlias,
                type: ResourceActionType::from($typeValue),
                label: (string) ($meta['label'] ?? ''),
                icon: isset($meta['icon']) ? (string) $meta['icon'] : null,
                buttonClass: isset($meta['buttonClass']) ? (string) $meta['buttonClass'] : null,
                priority: (int) ($meta['priority'] ?? 0),
                requiresConfirmation: (bool) ($meta['requiresConfirmation'] ?? false),
                template: isset($meta['template']) ? (string) $meta['template'] : null,
                routeName: (string) $routeName,
                path: (string) $route->getPath(),
                methods: $route->getMethods() !== [] ? $route->getMethods() : ['GET'],
                confirmMessage: $confirmMessage,
                modalId: $modalId,
                modalInternalComponent: $modalInternalComponent,
                modalContext: $modalContext,
            );

            $byAlias[$resourceAlias] ??= [];
            $byAlias[$resourceAlias][] = $descriptor;
        }

        foreach ($byAlias as $alias => $list) {
            usort(
                $list,
                static fn (ResourceActionDescriptor $a, ResourceActionDescriptor $b): int =>
                    $a->priority <=> $b->priority,
            );
            $byAlias[$alias] = $list;
        }

        $this->cacheByResourceAlias = $byAlias;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function isSupported(
        ResourceActionDescriptor $descriptor,
        ResourceInterface $resource,
        RequestConfiguration $requestConfiguration,
    ): bool {
        $route = $this->router->getRouteCollection()->get($descriptor->routeName);
        if (! $route instanceof Route) {
            return true;
        }

        $defaults = $route->getDefaults();
        $meta = $defaults['_resource_action'] ?? null;

        if (! is_array($meta)) {
            return true;
        }

        $supportsService = $meta['supports'] ?? null;
        if (! is_string($supportsService) || $supportsService === '') {
            return true;
        }

        if (! $this->supportsLocator->has($supportsService)) {
            return true;
        }

        $checker = $this->supportsLocator->get($supportsService);
        if (! $checker instanceof SupportsResourceActionInterface) {
            return true;
        }

        return $checker->supports($resource, $requestConfiguration);
    }
}
