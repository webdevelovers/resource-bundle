<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Metadata;

use InvalidArgumentException;

interface MetadataInterface
{
    public string $name { get; }
    public string $applicationName { get; }

    /** @var array<string,mixed> $parameters */
    public array $parameters { get; }
    public string $driver { get; }
    public string|null $templatesNamespace { get; }

    public function getAlias(): string;

    public function getHumanizedName(): string;

    public function getPluralName(): string;

    /** @throws InvalidArgumentException */
    public function getParameter(string $name): mixed;

    public function hasParameter(string $name): bool;

    public function getAction(string $name): string;

    public function hasAction(string $name): bool;

    /** @throws InvalidArgumentException */
    public function getClass(string $name): string;

    public function hasClass(string $name): bool;

    public function getServiceId(string $serviceName, string|null $suffix = null): string;

    public function getPermissionCode(string $permissionName): string;
}
