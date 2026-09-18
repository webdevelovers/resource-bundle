<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use WebDevelovers\ResourceBundle\CRUD\CRUDEvent;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

final readonly class RedirectHandler implements RedirectHandlerInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function redirectToResource(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        bool $allowRedirect = true,
    ): Response {
        try {
            return $this->redirectToRoute(
                $configuration,
                (string) $configuration->getRedirectRoute(CRUDEvent::SHOW->value, $allowRedirect),
                $configuration->getRedirectParameters($resource),
            );
        } catch (RouteNotFoundException) {
            return $this->redirectToRoute(
                $configuration,
                (string) $configuration->getRedirectRoute(CRUDEvent::INDEX->value, $allowRedirect),
                $configuration->getRedirectParameters($resource),
            );
        }
    }

    public function redirectToIndex(
        RequestConfiguration $configuration,
        ResourceInterface|null $resource = null,
        bool $allowRedirect = true,
    ): Response {
        return $this->redirectToRoute(
            $configuration,
            (string) $configuration->getRedirectRoute(CRUDEvent::INDEX->value, $allowRedirect),
            $configuration->getRedirectParameters($resource),
        );
    }

    /** @param array<string, mixed> $parameters */
    public function redirectToRoute(
        RequestConfiguration $configuration,
        string $route,
        array $parameters = [],
    ): Response {
        if ($route === 'referer') {
            return $this->redirectToReferer($configuration);
        }

        return $this->redirect($configuration, $this->router->generate($route, $parameters));
    }

    public function redirect(RequestConfiguration $configuration, string $url, int $status = 302): Response
    {
        if ($configuration->isHeaderRedirection()) {
            return new Response('', 200, [
                'X-WD-LOCATION' => $url . $configuration->getRedirectHash(),
            ]);
        }

        return new RedirectResponse($url . $configuration->getRedirectHash(), $status);
    }

    public function redirectToReferer(RequestConfiguration $configuration): Response
    {
        return $this->redirect($configuration, (string) $configuration->getRedirectReferer());
    }
}
