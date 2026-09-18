<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\Message;

use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;

readonly class CreateMessage
{
    public function __construct(
        public object $createData,
        public Metadata|null $metadata = null,
        public Parameters|null $parameters = null,
    ) {
    }
}
