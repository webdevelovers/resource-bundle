<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

interface RedirectHandlerInterface
{
    public function redirectToResource(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        bool $allowRedirect = true,
    ): Response;

    public function redirectToIndex(
        RequestConfiguration $configuration,
        ResourceInterface|null $resource = null,
        bool $allowRedirect = true,
    ): Response;

    /** @param array<string, mixed> $parameters */
    public function redirectToRoute(
        RequestConfiguration $configuration,
        string $route,
        array $parameters = [],
    ): Response;

    public function redirect(
        RequestConfiguration $configuration,
        string $url,
        int $status = 302,
    ): Response;

    public function redirectToReferer(RequestConfiguration $configuration): Response;
}
