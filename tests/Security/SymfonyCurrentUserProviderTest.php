<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use WebDevelovers\ResourceBundle\Security\SymfonyCurrentUserProvider;

final class SymfonyCurrentUserProviderTest extends TestCase
{
    public function testGetUserReturnsNullWhenTokenIsMissing(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn(null);

        $provider = new SymfonyCurrentUserProvider($tokenStorage);

        self::assertNull($provider->getUser());
    }

    public function testGetUserReturnsNullWhenTokenUserIsNull(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn(null);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $provider = new SymfonyCurrentUserProvider($tokenStorage);

        self::assertNull($provider->getUser());
    }

    public function testRequireUserThrowsWhenNoAuthenticatedUserIsAvailable(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn(null);

        $provider = new SymfonyCurrentUserProvider($tokenStorage);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to manage this kind of feature with no current user logged in.');

        $provider->requireUser();
    }

    public function testRequireUserReturnsAuthenticatedUser(): void
    {
        $user = $this->createStub(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $provider = new SymfonyCurrentUserProvider($tokenStorage);

        self::assertSame($user, $provider->requireUser());
    }
}
