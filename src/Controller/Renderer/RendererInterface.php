<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Renderer;

use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

interface RendererInterface
{
    /** @param array<string, mixed> $params */
    public function render(
        RequestConfiguration $configuration,
        string $action,
        array $params,
        int $statusCode = Response::HTTP_OK,
    ): Response;
}
