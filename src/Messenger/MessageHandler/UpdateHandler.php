<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use WebDevelovers\ResourceBundle\Messenger\Message\UpdateMessage;
use WebDevelovers\ResourceBundle\ResourceInterface;
use function assert;

#[AsMessageHandler]
readonly class UpdateHandler
{
    public function __invoke(UpdateMessage $updateMessage): ResourceInterface
    {
        $updateData = $updateMessage->updateData;
        assert($updateData instanceof ResourceInterface);
        /** Flush is not needed here, as the message bus will flush the entity manager */

        return $updateData;
    }
}
