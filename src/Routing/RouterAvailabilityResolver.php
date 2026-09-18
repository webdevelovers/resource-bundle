<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

final readonly class RouterAvailabilityResolver
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function isAvailable(RequestConfiguration $configuration, string $action): bool
    {
        $routeName = $configuration->getRouteName($action);

        return $this->router->getRouteCollection()->get($routeName) !== null;
    }

    /**
     * @param array<string> $actions
     *
     * @return array<string, bool>
     */
    public function resolve(
        RequestConfiguration $configuration,
        array $actions = ['index', 'show', 'create', 'update', 'delete'],
    ): array {
        $availability = [];

        foreach ($actions as $action) {
            $availability[$action] = $this->isAvailable($configuration, $action);
        }

        return $availability;
    }
}
