<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\Messenger\Message\ApplyTransitionMessage;
use WebDevelovers\ResourceBundle\Messenger\Message\CreateMessage;
use WebDevelovers\ResourceBundle\Messenger\Message\DeleteMessage;
use WebDevelovers\ResourceBundle\Messenger\Message\UpdateMessage;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;
use function assert;
use function sprintf;

readonly class ResourceMessageBus implements ResourceMessageBusInterface
{
    public function __construct(
        private MessageBusInterface $wdResourceBus,
    ) {
    }

    /** @throws ResourceBusException|ExceptionInterface */
    public function dispatchCreate(
        RequestConfiguration $configuration,
        object $subject,
    ): ResourceInterface {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? CreateMessage::class,
            params: [$subject, $configuration->metadata, $configuration->parameters],
        );

        $resource = $this->handleMessage($message);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    /** @throws ResourceBusException|ExceptionInterface */
    public function dispatchUpdate(
        RequestConfiguration $configuration,
        object $subject,
    ): ResourceInterface {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? UpdateMessage::class,
            params: [$subject, $configuration->metadata, $configuration->parameters],
        );

        $resource = $this->handleMessage($message);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    /** @throws ResourceBusException|ExceptionInterface */
    public function dispatchDelete(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): void {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? DeleteMessage::class,
            params: [$resource, $configuration->metadata, $configuration->parameters],
        );

        $this->handleMessage($message, requireHandledResult: false);
    }

    /** @throws ResourceBusException|ExceptionInterface */
    public function dispatchTransition(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): ResourceInterface {
        $graph = $configuration->getStateMachineGraph();
        $transition = $configuration->getStateMachineTransition();

        if (! $graph || ! $transition) {
            $exceptionMessage =
                'Invalid state machine configuration. Graph: ' . $graph . ' , transition: ' . $transition;

            throw new ResourceBusException($exceptionMessage);
        }

        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? ApplyTransitionMessage::class,
            params: [$resource, $configuration->metadata, $configuration->parameters, $graph, $transition],
        );

        $result = $this->handleMessage($message);
        assert($result instanceof ResourceInterface);

        return $result;
    }

    /** @throws ExceptionInterface */
    public function dispatchCreateAsync(
        RequestConfiguration $configuration,
        object $subject,
    ): Envelope {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? CreateMessage::class,
            params: [$subject, $configuration->metadata, $configuration->parameters],
        );

        return $this->wdResourceBus->dispatch($message);
    }

    /** @throws ExceptionInterface */
    public function dispatchUpdateAsync(
        RequestConfiguration $configuration,
        object $subject,
    ): Envelope {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? UpdateMessage::class,
            params: [$subject, $configuration->metadata, $configuration->parameters],
        );

        return $this->wdResourceBus->dispatch($message);
    }

    /** @throws ExceptionInterface */
    public function dispatchDeleteAsync(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): Envelope {
        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? DeleteMessage::class,
            params: [$resource, $configuration->metadata, $configuration->parameters],
        );

        return $this->wdResourceBus->dispatch($message);
    }

    /** @throws ResourceBusException|ExceptionInterface */
    public function dispatchTransitionAsync(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): Envelope {
        $graph = $configuration->getStateMachineGraph();
        $transition = $configuration->getStateMachineTransition();

        if (! $graph || ! $transition) {
            throw new ResourceBusException(
                'Invalid state machine configuration. Graph: ' . $graph . ' , transition: ' . $transition,
            );
        }

        $message = $this->messageInstance(
            messageClass: $configuration->getMessage() ?? ApplyTransitionMessage::class,
            params: [$resource, $configuration->metadata, $configuration->parameters, $graph, $transition],
        );

        return $this->wdResourceBus->dispatch($message);
    }

    /** @throws ResourceBusException|ExceptionInterface */
    private function handleMessage(object $message, bool $requireHandledResult = true): mixed
    {
        $envelope = $this->wdResourceBus->dispatch($message);
        $handledStamp = $envelope->last(HandledStamp::class);

        if ($handledStamp === null) {
            if (! $requireHandledResult) {
                return null;
            }

            throw new ResourceBusException(sprintf(
                'No synchronous handler result was found for message "%s". Use async dispatch methods for asynchronous processing.',
                $message::class,
            ));
        }

        return $handledStamp->getResult();
    }

    /** @param array<int, mixed> $params */
    private function messageInstance(string $messageClass, array $params): object
    {
        return new $messageClass(...$params);
    }
}
