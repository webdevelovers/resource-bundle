<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use WebDevelovers\ResourceBundle\Messenger\Message\DeleteMessage;

#[AsMessageHandler]
readonly class DeleteHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(DeleteMessage $deleteMessage): void
    {
        $resource = $deleteMessage->resource;
        $this->entityManager->remove($resource);
        /** Flush is not needed here, as the message bus will flush the entity manager */
    }
}
