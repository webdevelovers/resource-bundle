<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

final class ApplyTransition extends AbstractController
{
    public function __invoke(
        Request $request,
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        AuthorizationCheckerInterface $authorizationChecker,
        ResourceMessageBusInterface $messageBus,
        RendererInterface $renderer,
        RedirectHandlerInterface $redirectHandler,
    ): Response {
        try {
            $transitionName = self::transitionEventName($configuration);
            $authorizationChecker->denyAccessUnlessGranted($transitionName, $configuration, $resource);

            $resource = $messageBus->dispatchTransition($configuration, $resource);

            //$this->flashHelper->addSuccessFlash($configuration, self::flashMessageName($configuration), $resource);
        } catch (ResourceBusException $resourceBusException) {
            //$this->flashHelper->addErrorFlash($configuration, $resourceBusException->getMessage());
        }

        return $redirectHandler->redirectToResource($configuration, $resource);
    }

    /** @throws BadRequestHttpException */
    private static function transitionEventName(RequestConfiguration $configuration): string
    {
        $transition = $configuration->getStateMachineTransition();
        if ($transition === null) {
            throw new BadRequestHttpException();
        }

        return CRUDEvent::APPLY_TRANSITION->value . '.' . $transition;
    }

    /** @throws BadRequestHttpException */
    private static function flashMessageName(RequestConfiguration $configuration): string
    {
        $transition = $configuration->getStateMachineTransition();
        if ($transition === null) {
            throw new BadRequestHttpException();
        }

        $resourceName = $configuration->metadata->name;

        return $resourceName . '.transition.' . $transition;
    }
}
