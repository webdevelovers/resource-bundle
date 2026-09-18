<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

final class AutoResourceRouteLoader implements LoaderInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly LoaderInterface $delegatingLoader,
        private readonly ResourceLoader $resourceLoader,
    ) {
    }

    public function load(mixed $resource, string|null $type = null): mixed
    {
        $collection = $this->delegatingLoader->load($resource, $type);

        if (! $this->loaded && $collection instanceof RouteCollection) {
            $this->loaded = true;
            $collection->addCollection($this->resourceLoader->load('@wd.resource.tagged', 'wd.resource'));
        }

        return $collection;
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return $this->delegatingLoader->supports($resource, $type);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->delegatingLoader->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->delegatingLoader->setResolver($resolver);
    }
}
