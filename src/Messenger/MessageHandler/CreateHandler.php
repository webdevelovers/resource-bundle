<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use WebDevelovers\ResourceBundle\Messenger\Message\CreateMessage;
use WebDevelovers\ResourceBundle\ResourceInterface;
use function assert;

#[AsMessageHandler]
readonly class CreateHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(CreateMessage $createMessage): ResourceInterface
    {
        $resource = $createMessage->createData;
        assert($resource instanceof ResourceInterface);

        $this->entityManager->persist($resource);
        /** Flush is not needed here, as the message bus will flush the entity manager */

        return $resource;
    }
}
