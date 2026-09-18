<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Blame;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\User\UserInterface;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;

use function is_scalar;
use function method_exists;

readonly class BlameGenerator implements BlameGeneratorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    public function generate(): Blame
    {
        $user = $this->currentUserProvider->getUser();

        return $user instanceof UserInterface
            ? $this->generateUserBlame($user)
            : $this->generateSystemBlame();
    }

    private function generateUserBlame(UserInterface $user): Blame
    {
        $userIdentifier = $user->getUserIdentifier();
        $userID = $this->resolveUserId($user);

        if ($userID === null) {
            throw new RuntimeException('Unable to resolve authenticated user id for blame generation.');
        }

        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp();

        return new Blame($userID, $userIdentifier, null, $ip);
    }

    private function resolveUserId(UserInterface $user): string|null
    {
        if (method_exists($user, 'getId')) {
            $id = $user->getId();

            return $this->normalizeId($id);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (! $propertyAccessor->isReadable($user, 'id')) {
            return null;
        }

        $id = $propertyAccessor->getValue($user, 'id');

        return $this->normalizeId($id);
    }

    private function normalizeId(mixed $id): string|null
    {
        if ($id === null) {
            return null;
        }

        if (method_exists($id, 'toRfc4122')) {
            return $id->toRfc4122();
        }

        if (is_scalar($id)) {
            return (string) $id;
        }

        return method_exists($id, '__toString') ? (string) $id : null;
    }

    private function generateSystemBlame(): Blame
    {
        $userID = self::SYSTEM_UUID;
        $userIdentifier = 'system';
        $firewall = 'system';
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp();

        return new Blame($userID, $userIdentifier, $firewall, $ip);
    }
}
