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
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\CRUD\Update;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcherInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\ObjectMapper\DTOMapperInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
final class UpdateTest extends TestCase
{
    public function testInvokeDispatchesUpdateAndRedirectsToResourceWithEntityForm(): void
    {
        $configuration = $this->configuration(['form' => 'app.form.type']);
        $request = Request::create('/products/10/edit', 'POST');
        $resource = new UpdateDummyResource(10);
        $form = $this->formMock(true, true, $resource);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::once())
            ->method('denyAccessUnlessGranted')
            ->with('update', $configuration, $resource);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())
            ->method('create')
            ->with('app.form.type', $resource, [])
            ->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatchUpdate')
            ->with($configuration, $resource)
            ->willReturn($resource);

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
                'update',
                self::logicalOr(
                    self::equalTo('initialized'),
                    self::equalTo('request_handled'),
                    self::equalTo('before_dispatch'),
                    self::equalTo('dispatched'),
                ),
            );

        $response = (new Update($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $resource,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testInvokeDispatchesErrorAndRedirectsToResourceOnResourceBusException(): void
    {
        $configuration = $this->configuration(['form' => 'app.form.type']);
        $request = Request::create('/products/10/edit', 'POST');
        $resource = new UpdateDummyResource(10);
        $form = $this->formMock(true, true, $resource);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $dtoMapper = $this->createStub(DTOMapperInterface::class);

        $eventDispatcher = $this->createMock(ResourceActionEventDispatcherInterface::class);
        $eventDispatcher->expects(self::atLeastOnce())
            ->method('dispatch')
            ->with(
                $configuration,
                'update',
                self::logicalOr(
                    self::equalTo('initialized'),
                    self::equalTo('request_handled'),
                    self::equalTo('before_dispatch'),
                    self::equalTo('error'),
                ),
            );

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->method('dispatchUpdate')
            ->willThrowException(new ResourceBusException('failed'));

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::once())
            ->method('redirectToResource')
            ->with($configuration, $resource)
            ->willReturn(new Response('', Response::HTTP_FOUND));

        $response = (new Update($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $resource,
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
            'input' => UpdateDummyInput::class,
        ]);
        $request = Request::create('/products/10/edit', 'POST');
        $resource = new UpdateDummyResource(10);
        $input = new UpdateDummyInput('edited-name');
        $form = $this->formMock(true, false, $input);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);

        $dtoMapper = $this->createMock(DTOMapperInterface::class);
        $dtoMapper->expects(self::once())
            ->method('mapResourceToDTO')
            ->with($resource, UpdateDummyInput::class)
            ->willReturn($input);

        $eventDispatcher = $this->createStub(ResourceActionEventDispatcherInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $messageBus = $this->createMock(ResourceMessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatchUpdate');

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                $configuration,
                'update',
                self::callback(static function (array $params) use ($input): bool {
                    return $params['formID'] === 'product-update-form'
                        && $params['initialFormData'] === $input
                        && $params['input'] === $input;
                }),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            )
            ->willReturn(new Response('invalid', Response::HTTP_UNPROCESSABLE_ENTITY));

        $redirectHandler = $this->createMock(RedirectHandlerInterface::class);
        $redirectHandler->expects(self::never())->method('redirectToResource');

        $response = (new Update($dtoMapper, $eventDispatcher))(
            $request,
            $configuration,
            $resource,
            $authorizationChecker,
            $formFactory,
            $messageBus,
            $renderer,
            $redirectHandler,
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    /** @param array<string,mixed> $parameters */
    private function configuration(array $parameters): RequestConfiguration
    {
        return new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', ['classes' => ['model' => UpdateDummyResource::class]]),
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

final readonly class UpdateDummyResource implements ResourceInterface
{
    public function __construct(public int $id = 0)
    {
    }

    public function __toString(): string
    {
        return 'update-dummy';
    }
}

final class UpdateDummyInput
{
    public function __construct(public string $name = '')
    {
    }
}
