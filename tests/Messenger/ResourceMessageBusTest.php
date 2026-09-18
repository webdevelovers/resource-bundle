<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\Message\CreateMessage;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBus;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class ResourceMessageBusTest extends TestCase
{
    public function testDispatchCreateReturnsHandledResourceInSyncMode(): void
    {
        $resource = new DummyResource(10);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new CreateMessage($resource), [new HandledStamp($resource, 'create_handler')]));

        $messageBus = new ResourceMessageBus($bus);

        self::assertSame($resource, $messageBus->dispatchCreate($this->configuration(), $resource));
    }

    public function testDispatchCreateThrowsWhenNoHandledStampIsAvailable(): void
    {
        $resource = new DummyResource(10);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->willReturn(new Envelope(new CreateMessage($resource)));

        $messageBus = new ResourceMessageBus($bus);

        $this->expectException(ResourceBusException::class);
        $messageBus->dispatchCreate($this->configuration(), $resource);
    }

    public function testDispatchCreateAsyncSupportsDtoPayload(): void
    {
        $dto = new DummyDto('created-name');
        $capturedMessage = null;

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$capturedMessage): Envelope {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $configuration = $this->configuration(['message' => CreateMessage::class]);
        $messageBus = new ResourceMessageBus($bus);

        $messageBus->dispatchCreateAsync($configuration, $dto);

        self::assertInstanceOf(CreateMessage::class, $capturedMessage);
        self::assertSame($dto, $capturedMessage->createData);
    }

    /** @param array<string, mixed> $parameters */
    private function configuration(array $parameters = []): RequestConfiguration
    {
        return new RequestConfiguration(
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            parameters: new Parameters($parameters),
        );
    }
}

final readonly class DummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'resource';
    }
}

final readonly class DummyDto
{
    public function __construct(public string $name)
    {
    }
}
