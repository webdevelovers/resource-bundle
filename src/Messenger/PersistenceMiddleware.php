<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Throwable;

readonly class PersistenceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @throws Throwable */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->entityManager->beginTransaction();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            //TODO: log
            throw $exception;
        }

        return $envelope;
    }
}
