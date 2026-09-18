<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface as SymfonyAuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

readonly class AuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(
        private SymfonyAuthorizationChecker $symfonyAuthorizationChecker,
    ) {
    }

    public function denyAccessUnlessGranted(
        string $attribute,
        RequestConfiguration $configuration,
        ResourceInterface|null $subject = null,
    ): void {
        $securityAttribute = $this->securityAttribute($attribute, $configuration);
        if ($securityAttribute === null) {
            return;
        }

        if (! $this->symfonyAuthorizationChecker->isGranted($securityAttribute, $subject)) {
            $exception = new AccessDeniedException('Access Denied.');
            $exception->setAttributes([$securityAttribute]);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

    private function securityAttribute(
        string $attribute,
        RequestConfiguration $configuration,
    ): string|null {
        return $configuration->getPermission($attribute);
    }
}
