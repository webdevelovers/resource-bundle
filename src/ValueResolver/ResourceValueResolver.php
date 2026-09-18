<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactoryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;

use function array_merge;
use function assert;
use function sprintf;

final readonly class ResourceValueResolver implements ValueResolverInterface
{
    public function __construct(
        private MetadataRegistryInterface $registry,
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return iterable<int, object> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if ($argumentType === null || ! is_a($argumentType, ResourceInterface::class, true)) {
            return [];
        }

        $resourceAlias = $request->attributes->get('_resource_alias');
        if (! $resourceAlias) {
            throw new RuntimeException('Unable to autowire resource route without a resource alias');
        }

        $metadata = $this->registry->get($resourceAlias);
        $configuration = $this->requestConfigurationFactory->create($metadata, $request);
        $resource = $this->getResource($configuration, $metadata);
        if ($resource === null) {
            throw new NotFoundHttpException(sprintf('The "%s" has not been found', $metadata->getHumanizedName()));
        }

        return [$resource];
    }

    private function getResource(
        RequestConfiguration $requestConfiguration,
        MetadataInterface $metadata
    ): ResourceInterface|null
    {
        /** @var class-string $modelClass */
        $modelClass = $metadata->getClass('model');
        $repository = $this->entityManager->getRepository($modelClass);

        $request = $requestConfiguration->request;
        if ($request === null) {
            return null;
        }

        if ($request->attributes->has('id')) {
            $resource = $repository->find($request->attributes->get('id'));

            if ($resource !== null && ! $resource instanceof ResourceInterface) {
                throw new RuntimeException(sprintf('The entity was found but the resource "%s" is not an instance of "%s"', $metadata->getAlias(), ResourceInterface::class));
            }

            return $resource;
        }

        $criteria = [];
        if ($request->attributes->has('slug')) {
            $criteria = ['slug' => $request->attributes->get('slug')];
        }

        $criteria = array_merge($criteria, $requestConfiguration->getCriteria());

        if ($criteria === []) {
            return null;
        }

        $resource = $repository->findOneBy($criteria);

        return ($resource instanceof ResourceInterface) ? $resource : null;
    }
}
