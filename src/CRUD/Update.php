<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

use function assert;
use function in_array;

class Update extends AbstractController
{
    public function __construct(private readonly DTOMapperInterface $dtoMapper)
    {
    }

    public function __invoke(
        Request $request,
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        AuthorizationCheckerInterface $authorizationChecker,
        FormFactoryInterface $formFactory,
        ResourceMessageBusInterface $messageBus,
        RendererInterface $renderer,
        RedirectHandlerInterface $redirectHandler,
    ): Response {
        try {
            $authorizationChecker->denyAccessUnlessGranted(CRUDEvent::UPDATE->value, $configuration, $resource);

            $metadata = $configuration->metadata;
            $object = $this->getFormObject($configuration, $resource);

            $form = $formFactory->create(
                type: $configuration->getFormType(),
                data: $object,
                options: $configuration->getFormOptions(),
            );
            $form->handleRequest($request);

            if (
                in_array($request->getMethod(), ['POST', 'PUT', 'PATCH']) &&
                $form->isSubmitted() &&
                $form->isValid() &&
                ! $request->isXmlHttpRequest()
            ) {
                $resource = $messageBus->dispatchUpdate(
                    configuration: $configuration,
                    subject: $this->getPersistenceObject(
                        configuration: $configuration,
                        resource: $resource,
                        formData: $form->getData(),
                    ),
                );

                //$this->flashHelper->addSuccessFlash($configuration, CRUDEvent::UPDATE, $resource);

                return $redirectHandler->redirectToResource($configuration, $resource);
            }

            if (
                in_array($request->getMethod(), ['POST', 'PUT', 'PATCH']) &&
                $form->isSubmitted() &&
                ! $form->isValid()
            ) {
                $responseCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            }

            return $renderer->render($configuration, CRUDEvent::UPDATE->value, [
                'configuration' => $configuration,
                'metadata' => $metadata,
                'resource' => $resource,
                'input' => $object !== $resource ? $object : null,
                $metadata->name => $object,
                'form' => $form->createView(),
            ], $responseCode ?? Response::HTTP_OK);
        } catch (ResourceBusException) {
            //$this->flashHelper->addErrorFlash($configuration, $resourceBusException->getMessage());
        }

        return $redirectHandler->redirectToResource($configuration, $resource);
    }

    /** todo: review to wrap dto's in a provider that allow DI inside dto's */
    private function getFormObject(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): object {
        if ($configuration->getInput() === null) {
            return $resource;
        }

        $inputClass = $configuration->getInput();

        return $this->dtoMapper->mapResourceToDTO($resource, $inputClass);
    }

    private function getPersistenceObject(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
        object $formData,
    ): ResourceInterface {
        if ($configuration->getInput() === null) {
            assert($formData instanceof ResourceInterface);

            return $formData;
        }

        $resource = $this->dtoMapper->mapDTOToResource($formData, $resource);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }
}
