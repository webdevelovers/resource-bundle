<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Event;

use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

interface ResourceActionEventDispatcherInterface
{
    /** @param array<string, mixed> $context */
    public function dispatch(
        RequestConfiguration $configuration,
        string $action,
        string $stage,
        ResourceInterface|null $resource = null,
        object|null $input = null,
        \Throwable|null $error = null,
        array $context = [],
    ): void;
}

