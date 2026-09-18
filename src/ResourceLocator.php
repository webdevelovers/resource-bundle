<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;

readonly class ResourceLocator implements ResourceLocatorInterface
{
    public function __construct(
        private MetadataRegistryInterface $registry,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getResource(ResourceReference $reference): ResourceInterface
    {
        $metadata = $this->registry->get($reference->resourceAlias);

        /** @var class-string $modelClass */
        $modelClass = $metadata->getClass('model');
        $repository = $this->entityManager->getRepository($modelClass);

        $resource = $repository->find($reference->subjectId);

        if (! $resource instanceof ResourceInterface) {
            throw new RuntimeException(sprintf(
                'Resource with ID "%s" not found for alias "%s"',
                $reference->subjectId,
                $reference->resourceAlias
            ));
        }

        return $resource;
    }
}
