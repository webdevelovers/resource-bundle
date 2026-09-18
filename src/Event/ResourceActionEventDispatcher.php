<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

final readonly class ResourceActionEventDispatcher implements ResourceActionEventDispatcherInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function dispatch(
        RequestConfiguration $configuration,
        string $action,
        string $stage,
        ResourceInterface|null $resource = null,
        object|null $input = null,
        \Throwable|null $error = null,
        array $context = [],
    ): void {
        $event = new ResourceActionEvent(
            configuration: $configuration,
            action: $action,
            stage: $stage,
            resource: $resource,
            input: $input,
            error: $error,
            context: $context,
        );

        $this->eventDispatcher->dispatch($event, ResourceActionEvents::global($action, $stage));
        $this->eventDispatcher->dispatch($event, ResourceActionEvents::scoped($configuration, $action, $stage));
    }
}

