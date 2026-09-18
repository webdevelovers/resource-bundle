<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\Message;

use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\ResourceInterface;

readonly class DeleteMessage
{
    public function __construct(
        public ResourceInterface $resource,
        public Metadata $metadata,
        public Parameters $parameters,
    ) {
    }
}
