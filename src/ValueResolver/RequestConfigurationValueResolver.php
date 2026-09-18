<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactoryInterface;

final readonly class RequestConfigurationValueResolver implements ValueResolverInterface
{
    public function __construct(
        private MetadataRegistryInterface $registry,
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
    ) {
    }

    /** @return iterable<int, RequestConfiguration> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== RequestConfiguration::class) {
            return [];
        }

        $resourceAlias = $request->attributes->get('_resource_alias');

        if (! $resourceAlias) {
            return [];
        }

        $metadata = $this->registry->get($resourceAlias);
        $configuration = $this->requestConfigurationFactory->create($metadata, $request);

        return [$configuration];
    }
}
