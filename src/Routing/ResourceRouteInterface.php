<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Routing;

interface ResourceRouteInterface
{
    /**
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public static function config(): array;
}
