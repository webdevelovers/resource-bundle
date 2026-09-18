<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Messenger\MessageHandler;

use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Messenger\Message\UpdateMessage;
use WebDevelovers\ResourceBundle\Messenger\MessageHandler\UpdateHandler;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class UpdateHandlerTest extends TestCase
{
    public function testInvokeReturnsResource(): void
    {
        $resource = new UpdateHandlerDummyResource(1);
        $handler = new UpdateHandler();

        $result = $handler(new UpdateMessage(
            updateData: $resource,
            metadata: Metadata::fromAliasAndConfiguration('app.product', []),
            parameters: new Parameters([]),
        ));

        self::assertSame($resource, $result);
    }
}

final readonly class UpdateHandlerDummyResource implements ResourceInterface
{
    public function __construct(public int $id)
    {
    }

    public function __toString(): string
    {
        return 'update-resource';
    }
}
