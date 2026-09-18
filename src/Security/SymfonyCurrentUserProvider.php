<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Security;

use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class SymfonyCurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getUser(): UserInterface|null
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof UserInterface ? $user : null;
    }

    public function requireUser(): UserInterface
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new RuntimeException('Unable to manage this kind of feature with no current user logged in.');
        }

        return $user;
    }
}
