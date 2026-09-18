<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Event;

use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

final class ResourceActionEvents
{
    public static function global(string $action, string $stage): string
    {
        return 'wd.resource.action.' . $action . '.' . $stage;
    }

    public static function scoped(RequestConfiguration $configuration, string $action, string $stage): string
    {
        return 'wd.resource.action.' . $configuration->metadata->getAlias() . '.' . $action . '.' . $stage;
    }
}

