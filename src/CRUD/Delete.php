<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

class Delete extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(
        Request $request,
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        AuthorizationCheckerInterface $authorizationChecker,
        ResourceMessageBusInterface $messageBus,
        RedirectHandlerInterface $redirectHandler,
    ): Response {
        $authorizationChecker->denyAccessUnlessGranted(CRUDEvent::DELETE->value, $configuration, $resource);
        $this->checkCsrfToken($request, $configuration, $resource);
        $clonedResource = clone $resource;

        try {
            $messageBus->dispatchDelete($configuration, $resource);
            //$this->flashHelper->addSuccessFlash($configuration, CRUDEvent::DELETE, $clonedResource);
        } catch (ResourceBusException) {
            //$this->flashHelper->addErrorFlash($configuration, $resourceBusException->getMessage());
        }

        return $redirectHandler->redirectToIndex($configuration);
    }

    private function checkCsrfToken(
        Request $request,
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): void {
        if (! $configuration->isCsrfProtectionEnabled()) {
            throw $this->createAccessDeniedException('CSRF protection disabled.');
        }

        $queryToken = $request->request->get('_csrf_token');
        $token = new CsrfToken((string) $resource->id, $queryToken);

        if (! $this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
