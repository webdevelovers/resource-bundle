<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Renderer;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

readonly class TwigRenderer implements RendererInterface
{
    public function __construct(private Environment $environment)
    {
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function render(
        RequestConfiguration $configuration,
        string $action,
        array $params,
        int $statusCode = Response::HTTP_OK,
    ): Response {
        return new Response(
            $this->environment->render($configuration->getTemplate($action), $params),
            $statusCode,
        );
    }
}
