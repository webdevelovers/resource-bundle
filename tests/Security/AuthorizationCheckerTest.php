<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface as SymfonyAuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationChecker;

final class AuthorizationCheckerTest extends TestCase
{
    public function testDenyAccessUnlessGrantedWithGrantedPermission(): void
    {
        $configuration = $this->configuration(new Parameters(['permission' => true]));
        $checker = $this->createMock(SymfonyAuthorizationChecker::class);
        $checker->expects(self::once())
            ->method('isGranted')
            ->with('app.product.update', null)
            ->willReturn(true);

        (new AuthorizationChecker($checker))->denyAccessUnlessGranted('update', $configuration);

        self::assertTrue(true);
    }

    public function testDenyAccessUnlessGrantedThrowsWhenDenied(): void
    {
        $configuration = $this->configuration(new Parameters(['permission' => true]));
        $subject = new SecurityDummyResource(7);

        $checker = $this->createMock(SymfonyAuthorizationChecker::class);
        $checker->expects(self::once())
            ->method('isGranted')
            ->with('app.product.delete', $subject)
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        (new AuthorizationChecker($checker))->denyAccessUnlessGranted('delete', $configuration, $subject);
    }

    public function testDenyAccessUnlessGrantedSkipsWhenPermissionIsNotConfigured(): void
    {
        $configuration = $this->configuration(new Parameters([]));
        $checker = $this->createMock(SymfonyAuthorizationChecker::class);
        $checker->expects(self::never())->method('isGranted');

        (new AuthorizationChecker($checker))->denyAccessUnlessGranted('show', $configuration);

        self::assertTrue(true);
    }

    public function testDenyAccessUnlessGrantedUsesExplicitPermissionString(): void
    {
        $configuration = $this->configuration(new Parameters(['permission' => 'custom.permission']));
        $checker = $this->createMock(SymfonyAuthorizationChecker::class);
        $checker->expects(self::once())
            ->method('isGranted')
            ->with('custom.permission', null)
            ->willReturn(true);

        (new AuthorizationChecker($checker))->denyAccessUnlessGranted('create', $configuration);

        self::assertTrue(true);
    }

    private function configuration(Parameters $parameters): RequestConfiguration
    {
        return new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            parameters: $parameters,
        );
    }
}

final readonly class SecurityDummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'security-resource';
    }
}
