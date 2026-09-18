<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Messenger\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Messenger\Message\DeleteMessage;
use WebDevelovers\ResourceBundle\Messenger\MessageHandler\DeleteHandler;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class DeleteHandlerTest extends TestCase
{
    public function testInvokeRemovesResource(): void
    {
        $resource = new DeleteHandlerDummyResource(1);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($resource);

        $handler = new DeleteHandler($entityManager);
        $handler(new DeleteMessage(
            resource: $resource,
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            parameters: new Parameters([]),
        ));

        self::assertTrue(true);
    }
}

final readonly class DeleteHandlerDummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'delete-resource';
    }
}
