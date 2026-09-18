<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Action;

use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

interface SupportsResourceActionInterface
{
    public function supports(ResourceInterface $resource, RequestConfiguration $requestConfiguration): bool;
}
