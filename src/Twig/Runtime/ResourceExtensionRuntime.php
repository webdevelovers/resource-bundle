<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Runtime;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;
use Twig\Extension\RuntimeExtensionInterface;
use WebDevelovers\ResourceBundle\Action\ResourceActionCollector;
use WebDevelovers\ResourceBundle\Action\ResourceActionDescriptor;
use WebDevelovers\ResourceBundle\Action\ResourceActionType;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Routing\RouteFactory;
use WebDevelovers\ResourceBundle\Routing\RouterAvailabilityResolver;
use function assert;

readonly class ResourceExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private RouterInterface            $router,
        private MetadataRegistryInterface  $registry,
        private EntityManagerInterface     $entityManager,
        private ResourceActionCollector    $collector,
        private RouterAvailabilityResolver $routerAvailabilityResolver,
    ) {
    }

    public function resourceUrl(Uuid $subject, string $resourceAlias, string|null $section): string
    {
        $metadata = $this->registry->get($resourceAlias);

        /** @var class-string $className */
        $className = $metadata->getClass('model');

        $entity = $this->entityManager->getRepository($className)->find($subject);
        if ($entity === null) {
            return '#';
        }

        assert($entity instanceof ResourceInterface);

        $routeName = RouteFactory::generateRouteName('wd', $metadata->name, 'show', $section);

        return $this->router->generate($routeName, ['id' => $entity->id]);
    }

    /** @return list<ResourceActionDescriptor> */
    public function resourceActions(
        ResourceInterface $resource,
        RequestConfiguration $requestConfiguration,
        string $resourceAlias,
        string|null $type = null,
    ): array {
        $enumType = $type !== null ? ResourceActionType::from($type) : null;

        return $this->collector->forResource($resource, $requestConfiguration, $resourceAlias, $enumType);
    }

    public function routeAvailable(RequestConfiguration $configuration, string $action): bool
    {
        return $this->routerAvailabilityResolver->isAvailable($configuration, $action);
    }

    /**
     * @param list<string> $actions
     *
     * @return array<string, bool>
     */
    public function routesAvailable(
        RequestConfiguration $configuration,
        array $actions = ['index', 'show', 'create', 'update', 'delete', 'bulk_delete'],
    ): array {
        return $this->routerAvailabilityResolver->resolve($configuration, $actions);
    }
}
