<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\Message;

use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;

readonly class UpdateMessage
{
    public function __construct(
        public object $updateData,
        public Metadata $metadata,
        public Parameters $parameters,
    ) {
    }
}
