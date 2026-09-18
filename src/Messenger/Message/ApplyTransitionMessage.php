<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger\Message;

use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\ResourceInterface;

readonly class ApplyTransitionMessage
{
    public function __construct(
        public ResourceInterface $subjectID,
        public Metadata $metadata,
        public Parameters $parameters,
        public string $graph,
        public string $transition,
    ) {
    }
}
