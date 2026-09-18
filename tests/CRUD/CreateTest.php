<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\CRUD;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use WebDevelovers\ResourceBundle\CRUD\Create;
use WebDevelovers\ResourceBundle\CRUD\DTOMapperInterface;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcherInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
final class CreateTest extends TestCase
{
    public function testInvokeDispatchesCreateAndRedirectsToResourceWithEntityForm(): void
    {
        $configuration = $this->configuration(['form' => 'app.form.type']);
        $request = Request::create('/products/new', 'POST');
        $resource = new CreateDummyResource(10);
        $form = $this->formMock(true, true, $resource);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::once())
            ->method('denyAccessUnlessGranted')
            ->with('create', $configuration, null);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())
            ->method('create')
            ->with('app.form.type', self::isInstanceOf(CreateDummyResource::class), [])
            ->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatchCreate')
            ->with($configuration, $resource)
            ->willReturn($resource);
        $messageBus->expects(self::never())->method('dispatchCreateAsync');

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::once())
            ->method('redirectToResource')
            ->with($configuration, $resource)
            ->willReturn(new Response('', Response::HTTP_FOUND));

        $dtoMapper = $this->createMock(DTOMapperInterface::class);
        $dtoMapper->expects(self::never())->method('mapDTOToResource');

        $eventDispatcher = $this->createMock(ResourceActionEventDispatcherInterface::class);
        $eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->with(
                $configuration,
                'create',
                self::logicalOr(
                    self::equalTo('initialized'),
                    self::equalTo('request_handled'),
                    self::equalTo('before_dispatch'),
                    self::equalTo('dispatched'),
                ),
            );

        $response = (new Create($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testInvokeMapsDtoAndDispatchesCreateForDtoForm(): void
    {
        $configuration = $this->configuration([
            'form' => 'app.form.type',
            'input' => CreateDummyInput::class,
        ]);
        $request = Request::create('/products/new', 'POST');
        $formData = new CreateDummyInput('dto-name');
        $resource = new CreateDummyResource(20);
        $form = $this->formMock(true, true, $formData);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $dtoMapper = $this->createMock(DTOMapperInterface::class);
        $dtoMapper->expects(self::once())
            ->method('mapDTOToResource')
            ->with($formData, CreateDummyResource::class)
            ->willReturn($resource);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatchCreate')
            ->with($configuration, $resource)
            ->willReturn($resource);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->method('redirectToResource')->willReturn(new Response('', Response::HTTP_FOUND));

        $eventDispatcher = $this->createStub(ResourceActionEventDispatcherInterface::class);

        $response = (new Create($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testInvokeReturns422AndTemplateContextWhenFormIsInvalid(): void
    {
        $configuration = $this->configuration([
            'form' => 'app.form.type',
            'input' => CreateDummyInput::class,
            'vars' => ['create' => ['live_component' => true]],
        ]);
        $request = Request::create('/products/new', 'POST');
        $formData = new CreateDummyInput('dto-name');
        $form = $this->formMock(true, false, $formData);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $dtoMapper = $this->createStub(DTOMapperInterface::class);
        $eventDispatcher = $this->createStub(ResourceActionEventDispatcherInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatchCreate');

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $configuration,
                'create',
                self::callback(static function (array $params): bool {
                    return $params['form_mode'] === 'dto-form'
                        && $params['is_live_component'] === true
                        && $params['input'] instanceof CreateDummyInput;
                }),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            )
            ->willReturn(new Response('invalid', Response::HTTP_UNPROCESSABLE_ENTITY));

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::never())->method('redirectToResource');
        $redirectHandler->expects(self::never())->method('redirectToIndex');

        $response = (new Create($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testInvokeDispatchesAsyncAndRedirectsToIndexWhenConfigured(): void
    {
        $configuration = $this->configuration([
            'form' => 'app.form.type',
            'async' => ['create' => true],
        ]);
        $request = Request::create('/products/new', 'POST');
        $resource = new CreateDummyResource(44);
        $form = $this->formMock(true, true, $resource);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $dtoMapper = $this->createStub(DTOMapperInterface::class);
        $eventDispatcher = $this->createStub(ResourceActionEventDispatcherInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatchCreateAsync')
            ->with($configuration, $resource)
            ->willReturn(new Envelope(new \stdClass()));
        $messageBus->expects(self::never())->method('dispatchCreate');

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::once())
            ->method('redirectToIndex')
            ->with($configuration)
            ->willReturn(new Response('', Response::HTTP_FOUND));

        $response = (new Create($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testInvokeRedirectsToIndexOnResourceBusException(): void
    {
        $configuration = $this->configuration(['form' => 'app.form.type']);
        $request = Request::create('/products/new', 'POST');
        $resource = new CreateDummyResource(88);
        $form = $this->formMock(true, true, $resource);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $dtoMapper = $this->createStub(DTOMapperInterface::class);

        $eventDispatcher = $this->createMock(ResourceActionEventDispatcherInterface::class);
        $eventDispatcher->expects(self::atLeastOnce())
            ->method('dispatch')
            ->with(
                $configuration,
                'create',
                self::logicalOr(self::equalTo('initialized'), self::equalTo('request_handled'), self::equalTo('before_dispatch'), self::equalTo('error')),
            );

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->method('dispatchCreate')
            ->willThrowException(new ResourceBusException('failed'));

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::once())
            ->method('redirectToIndex')
            ->with($configuration)
            ->willReturn(new Response('', Response::HTTP_FOUND));

        $response = (new Create($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /** @param array<string,mixed> $parameters */
    private function configuration(array $parameters): RequestConfiguration
    {
        return new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', ['classes' => ['model' => CreateDummyResource::class]]),
            parameters: new Parameters($parameters),
        );
    }

    private function formMock(bool $submitted, bool $valid, object $data): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('handleRequest');
        $form->method('isSubmitted')->willReturn($submitted);
        $form->method('isValid')->willReturn($valid);
        $form->method('getData')->willReturn($data);
        $form->method('createView')->willReturn(new FormView());

        return $form;
    }
}

final class CreateDummyInput
{
    public function __construct(public string $name = '')
    {
    }
}

final readonly class CreateDummyResource implements ResourceInterface
{
    public function __construct(public int $id = 0)
    {
    }

    public function __toString(): string
    {
        return 'create-dummy';
    }
}
