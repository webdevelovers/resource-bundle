<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebDevelovers\ResourceBundle\Twig\Runtime\ResourceExtensionRuntime;

class ResourceExtension extends AbstractExtension
{
    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('resource_url', [ResourceExtensionRuntime::class, 'resourceUrl']),
            new TwigFunction('resource_actions', [ResourceExtensionRuntime::class, 'resourceActions']),

            new TwigFunction('route_available', [ResourceExtensionRuntime::class, 'routeAvailable']),
            new TwigFunction('routes_available', [ResourceExtensionRuntime::class, 'routesAvailable']),
        ];
    }
}
