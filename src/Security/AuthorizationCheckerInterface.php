<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Security;

use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

interface AuthorizationCheckerInterface
{
    public function denyAccessUnlessGranted(
        string $attribute,
        RequestConfiguration $configuration,
        ResourceInterface|null $subject = null,
    ): void;
}
