<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Metadata;

use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;
use InvalidArgumentException;

use function array_key_exists;
use function explode;
use function preg_replace;
use function sprintf;
use function str_contains;
use function strtolower;
use function trim;

final class Metadata implements MetadataInterface
{
    private(set) string|null $templatesNamespace = null;

    private static InflectorObject|null $inflectorInstance = null;

    /** @param array<string,mixed> $parameters */
    private function __construct(
        private(set) readonly string $name,
        private(set) readonly string $applicationName,
        private(set) readonly array $parameters,
        private(set) readonly string $driver = 'doctrine/orm',
    ) {
        $this->templatesNamespace = $parameters['templates'] ?? null;
    }

    /** @param array<string,mixed> $parameters */
    public static function fromAliasAndConfiguration(string $alias, array $parameters): self
    {
        [$applicationName, $name] = self::parseAlias($alias);

        return new self($name, $applicationName, $parameters);
    }

    public static function setInflector(InflectorObject $inflector): void
    {
        self::$inflectorInstance = $inflector;
    }

    private static function getInflector(): InflectorObject
    {
        return self::$inflectorInstance ??= InflectorFactory::create()->build();
    }

    public function getAlias(): string
    {
        return $this->applicationName . '.' . $this->name;
    }

    public function getHumanizedName(): string
    {
        $humanized = preg_replace('/([A-Z])/', '_$1', $this->name);
        if ($humanized === null) {
            return strtolower($this->name);
        }

        return strtolower(trim((string) preg_replace('/[_\s]+/', ' ', $humanized)));
    }

    public function getPluralName(): string
    {
        return self::getInflector()->pluralize($this->name);
    }

    public function getParameter(string $name): mixed
    {
        if (! $this->hasParameter($name)) {
            throw new InvalidArgumentException(sprintf(
                'Parameter "%s" is not configured for resource "%s".',
                $name,
                $this->getAlias(),
            ));
        }

        return $this->parameters[$name];
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getAction(string $name): string
    {
        $controllerClasses = $this->controllerClasses();
        if (isset($controllerClasses[$name])) {
            return $controllerClasses[$name];
        }

        throw new InvalidArgumentException(sprintf(
            'Action "%s" is not configured for resource "%s".',
            $name,
            $this->getAlias(),
        ));
    }

    public function hasAction(string $name): bool
    {
        return isset($this->controllerClasses()[$name]);
    }

    public function getClass(string $name): string
    {
        if (! $this->hasClass($name)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" is not configured for resource "%s".',
                $name,
                $this->getAlias(),
            ));
        }

        return $this->classes()[$name];
    }

    public function hasClass(string $name): bool
    {
        return isset($this->classes()[$name]);
    }

    public function getServiceId(string $serviceName, string|null $suffix = null): string
    {
        if ($suffix !== null) {
            return sprintf('%s.%s.%s.%s', $this->applicationName, $serviceName, $this->name, $suffix);
        }

        return sprintf('%s.%s.%s', $this->applicationName, $serviceName, $this->name);
    }

    public function getPermissionCode(string $permissionName): string
    {
        return sprintf('%s.%s.%s', $this->applicationName, $this->name, $permissionName);
    }

    /** @return array<int,string> */
    private static function parseAlias(string $alias): array
    {
        if (! str_contains($alias, '.')) {
            throw new InvalidArgumentException(sprintf(
                'Invalid alias "%s" supplied, it should conform to the following format "<applicationName>.<name>".',
                $alias,
            ));
        }

        return explode('.', $alias, 2);
    }

    /** @return array<string, string> */
    private function classes(): array
    {
        $classes = $this->parameters['classes'] ?? [];

        return is_array($classes) ? $classes : [];
    }

    /** @return array<string, string> */
    private function controllerClasses(): array
    {
        $controllerClasses = $this->classes()['controller'] ?? [];

        return is_array($controllerClasses) ? $controllerClasses : [];
    }
}
