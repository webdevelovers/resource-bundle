<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

final class Show extends AbstractController
{
    public function __invoke(
        ResourceInterface $object,
        RequestConfiguration $configuration,
        AuthorizationCheckerInterface $authorizationChecker,
        RendererInterface $renderer,
    ): Response {
        $metadata = $configuration->metadata;
        $authorizationChecker->denyAccessUnlessGranted(CRUDEvent::SHOW->value, $configuration, $object);

        $output = $this->handleOutput($configuration, $object);

        return $renderer->render($configuration, CRUDEvent::SHOW->value, [
            'configuration' => $configuration,
            'metadata' => $metadata,
            'resource' => $output,
            $metadata->name => $object,
        ]);
    }

    protected function handleOutput(
        RequestConfiguration $configuration,
        ResourceInterface $object,
    ): ResourceInterface {
        // TODO: dto mapping

        return $object;
    }
}
