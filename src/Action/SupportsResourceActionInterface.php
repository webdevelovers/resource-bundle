<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Action;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

#[AutoconfigureTag('wd.resource.resource_action_support')]
interface SupportsResourceActionInterface
{
    public function supports(ResourceInterface $resource, RequestConfiguration $requestConfiguration): bool;
}
