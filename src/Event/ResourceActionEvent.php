<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

final class ResourceActionEvent extends Event
{
    /** @param array<string, mixed> $context */
    public function __construct(
        public readonly RequestConfiguration $configuration,
        public readonly string $action,
        public readonly string $stage,
        public readonly ResourceInterface|null $resource = null,
        public readonly object|null $input = null,
        public readonly \Throwable|null $error = null,
        public readonly array $context = [],
    ) {
    }
}

