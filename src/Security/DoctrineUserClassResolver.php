<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class DoctrineUserClassResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /** @return class-string<UserInterface> */
    public function resolve(): string
    {
        $metadataList = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadataList as $metadata) {
            $class = $metadata->getName();
            if (is_a($class, UserInterface::class, true)) {
                return $class;
            }
        }

        $user = $this->currentUserProvider->getUser();
        if ($user !== null) {
            /** @var class-string<UserInterface> $class */
            $class = $user::class;

            return $class;
        }

        throw new RuntimeException('Unable to resolve the Doctrine-managed user entity class. Ensure your application maps a concrete user entity implementing UserInterface.');
    }
}
