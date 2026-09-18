<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Controller\RedirectHandler;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class RedirectHandlerTest extends TestCase
{
    public function testRedirectToResourceFallsBackToIndexWhenShowRouteDoesNotExist(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $calls = 0;

        $router->expects(self::exactly(2))
            ->method('generate')
            ->willReturnCallback(function (string $route, array $parameters) use (&$calls): string {
                ++$calls;

                if ($calls === 1) {
                    self::assertSame('app_product_show', $route);
                    self::assertSame(['id' => 10], $parameters);

                    throw new RouteNotFoundException();
                }

                self::assertSame('app_product_index', $route);
                self::assertSame(['id' => 10], $parameters);

                return '/products';
            });

        $handler = new RedirectHandler($router);
        $response = $handler->redirectToResource($this->configuration(), new DummyResource(10));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/products', $response->headers->get('Location'));
    }

    public function testRedirectReturnsHeaderRedirectionWhenConfigured(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $handler = new RedirectHandler($router);

        $response = $handler->redirect(
            $this->configuration([
                'redirect' => ['header' => true, 'hash' => 'saved'],
            ], new Request()),
            '/target',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('/target#saved', $response->headers->get('X-WD-LOCATION'));
    }

    public function testRedirectToRouteUsesRefererShortcut(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $request = new Request();
        $request->headers->set('referer', '/from-header');

        $handler = new RedirectHandler($router);
        $response = $handler->redirectToRoute(
            $this->configuration(['redirect' => ['referer' => true]], $request),
            'referer',
        );

        self::assertSame('/from-header', $response->headers->get('Location'));
    }

    /** @param array<string, mixed> $parameters */
    private function configuration(array $parameters = [], Request|null $request = null): RequestConfiguration
    {
        return new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            request: $request,
            parameters: new Parameters($parameters),
        );
    }
}

final readonly class DummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'dummy';
    }
}
