<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Messenger\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Messenger\Message\CreateMessage;
use WebDevelovers\ResourceBundle\Messenger\MessageHandler\CreateHandler;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class CreateHandlerTest extends TestCase
{
    public function testInvokePersistsAndReturnsResource(): void
    {
        $resource = new HandlerDummyResource(1);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($resource);

        $handler = new CreateHandler($entityManager);

        self::assertSame($resource, $handler(new CreateMessage($resource)));
    }
}

final readonly class HandlerDummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'handler-resource';
    }
}
