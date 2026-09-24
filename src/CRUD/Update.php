<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcherInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\ObjectMapper\DTOMapperInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;
use function assert;
use function sprintf;
use function str_replace;

class Update extends AbstractController
{
    public function __construct(
        private readonly DTOMapperInterface $dtoMapper,
        private readonly ResourceActionEventDispatcherInterface $eventDispatcher,
    ) {}

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
        $action = CRUDEvent::UPDATE->value;

        try {
            $authorizationChecker->denyAccessUnlessGranted($action, $configuration, $resource);

            $metadata = $configuration->metadata;
            $object = $this->getFormObject($configuration, $resource);
            $this->eventDispatcher->dispatch($configuration, $action, 'initialized', resource: $resource, input: $object);

            $form = $formFactory->create(
                type: $configuration->getFormType(),
                data: $object,
                options: $configuration->getFormOptions(),
            );
            $form->handleRequest($request);
            $this->eventDispatcher->dispatch($configuration, $action, 'request_handled', resource: $resource, input: $object);

            if ($this->isUpdateSubmission($request, $form) && $form->isValid()) {
                $this->eventDispatcher->dispatch($configuration, $action, 'before_dispatch', resource: $resource, input: $object);

                $resource = $messageBus->dispatchUpdate(
                    configuration: $configuration,
                    subject: $this->getPersistenceObject(
                        configuration: $configuration,
                        resource: $resource,
                        formData: $form->getData(),
                    ),
                );
                $this->eventDispatcher->dispatch($configuration, $action, 'dispatched', resource: $resource, input: $object);

                //$this->flashHelper->addSuccessFlash($configuration, CRUDEvent::UPDATE, $resource);

                return $redirectHandler->redirectToResource($configuration, $resource);
            }

            if ($this->isUpdateSubmission($request, $form) && ! $form->isValid()) {
                $responseCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            }

            return $renderer->render($configuration, $action, [
                'configuration' => $configuration,
                'metadata' => $metadata,
                'resource' => $resource,
                'input' => $object !== $resource ? $object : null,
                'initialFormData' => $object,
                'formID' => $this->buildFormId($configuration, $action),
                'action' => $action,
                $metadata->name => $object,
                'form' => $form->createView(),
            ], $responseCode ?? Response::HTTP_OK);
        } catch (ResourceBusException|\Throwable $exception) {
            $this->eventDispatcher->dispatch($configuration, $action, 'error', resource: $resource, error: $exception);
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

    private function isUpdateSubmission(Request $request, FormInterface $form): bool
    {
        return $request->isMethod('POST') && $form->isSubmitted() && ! $request->isXmlHttpRequest();
    }

    private function buildFormId(RequestConfiguration $configuration, string $action): string
    {
        $metadataName = str_replace('.', '-', $configuration->metadata->name);

        return sprintf('%s-%s-form', $metadataName, $action);
    }
}
