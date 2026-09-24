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
use function is_array;
use function is_bool;
use function sprintf;
use function str_replace;

final class Create extends AbstractController
{
    public function __construct(
        private readonly DTOMapperInterface $dtoMapper,
        private readonly ResourceActionEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(
        Request $request,
        RequestConfiguration $configuration,
        AuthorizationCheckerInterface $authorizationChecker,
        FormFactoryInterface $formFactory,
        ResourceMessageBusInterface $messageBus,
        RendererInterface $renderer,
        RedirectHandlerInterface $redirectHandler,
    ): Response {
        $action = CRUDEvent::CREATE->value;

        try {
            $authorizationChecker->denyAccessUnlessGranted($action, $configuration);
            $metadata = $configuration->metadata;

            $object = $this->getFormObject($configuration);
            $this->eventDispatcher->dispatch($configuration, $action, 'initialized', input: $object);

            $form = $formFactory->create($configuration->getFormType(), $object, $configuration->getFormOptions());
            $form->handleRequest($request);
            $this->eventDispatcher->dispatch($configuration, $action, 'request_handled', input: $object);

            if ($this->isCreateSubmission($request, $form) && $form->isValid()) {
                $persistenceObject = $this->getPersistenceObject($configuration, $form->getData());
                $this->eventDispatcher->dispatch($configuration, $action, 'before_dispatch', input: $object);

                if ($this->isAsync($configuration)) {
                    $messageBus->dispatchCreateAsync($configuration, $persistenceObject);
                    $this->eventDispatcher->dispatch($configuration, $action, 'dispatched_async', input: $object);

                    return $redirectHandler->redirectToIndex($configuration);
                }

                $resource = $messageBus->dispatchCreate($configuration, $persistenceObject);
                $this->eventDispatcher->dispatch($configuration, $action, 'dispatched', resource: $resource, input: $object);

                //$this->flashHelper->addSuccessFlash($configuration, CRUDEvent::CREATE, $resource);
                return $redirectHandler->redirectToResource($configuration, $resource);
            }

            if ($this->isCreateSubmission($request, $form) && ! $form->isValid()) {
                $responseCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            }

            return $renderer->render($configuration, $action, [
                'configuration' => $configuration,
                'metadata' => $metadata,
                'resource' => $object,
                'input' => $object,
                'initialFormData' => $object,
                'formID' => $this->buildFormId($configuration, $action),
                'action' => $action,
                'form_mode' => $configuration->getInput() === null ? 'entity-form' : 'dto-form',
                'is_live_component' => $this->isLiveComponentEnabled($configuration),
                $metadata->name => $object,
                'form' => $form->createView(),
            ], $responseCode ?? Response::HTTP_OK);
        } catch (ResourceBusException|\Throwable $exception) {
            dump($exception);
            $this->eventDispatcher->dispatch($configuration, $action, 'error', error: $exception);
            //$this->flashHelper->addErrorFlash($configuration, $resourceBusException->getMessage());
        }

        return $redirectHandler->redirectToIndex($configuration);
    }

    /** todo: review to wrap dto's in a provider that allow DI inside dto's */
    private function getFormObject(RequestConfiguration $configuration): object
    {
        $metadata = $configuration->metadata;
        $resourceClass = $metadata->getClass('model');
        if ($configuration->getInput() === null) {
            return new $resourceClass();
        }

        $inputClass = $configuration->getInput();

        return new $inputClass();
    }

    private function getPersistenceObject(RequestConfiguration $configuration, object $formData): ResourceInterface
    {
        dump($formData);
        $metadata = $configuration->metadata;
        $resourceClass = $metadata->getClass('model');
        if ($configuration->getInput() === null) {
            assert($formData instanceof ResourceInterface);

            return $formData;
        }
        dump('mapping');
        $resource = $this->dtoMapper->mapDTOToResource($formData, $resourceClass);
        dump($resource);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    private function isCreateSubmission(Request $request, FormInterface $form): bool
    {
        return $request->isMethod('POST') && $form->isSubmitted() && ! $request->isXmlHttpRequest();
    }

    private function isAsync(RequestConfiguration $configuration): bool
    {
        $async = $configuration->parameters->get('async', false);
        if (is_bool($async)) {
            return $async;
        }

        if (! is_array($async)) {
            return false;
        }

        $createAsync = $async[CRUDEvent::CREATE->value] ?? false;

        return $createAsync === true;
    }

    private function isLiveComponentEnabled(RequestConfiguration $configuration): bool
    {
        $vars = $configuration->getVars();
        $liveComponent = $vars['live_component'] ?? ($vars['create']['live_component'] ?? false);

        return $liveComponent === true;
    }

    private function buildFormId(RequestConfiguration $configuration, string $action): string
    {
        $metadataName = str_replace('.', '-', $configuration->metadata->name);

        return sprintf('%s-%s-form', $metadataName, $action);
    }
}
