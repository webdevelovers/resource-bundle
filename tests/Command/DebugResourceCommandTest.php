<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use WebDevelovers\ResourceBundle\Command\DebugResourceCommand;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;

use function strpos;

final class DebugResourceCommandTest extends TestCase
{
    public function testListResourcesRendersAliasesInSortedOrder(): void
    {
        $registry = new class implements MetadataRegistryInterface {
            /** @var array<string, MetadataInterface> */
            private array $resources = [];

            public function __construct()
            {
                $this->resources = [
                    'wd.zeta' => $this->createMetadata('zeta', 'wd'),
                    'wd.alpha' => $this->createMetadata('alpha', 'wd'),
                ];
            }

            public function getAll(): array
            {
                return $this->resources;
            }

            public function get(string $alias): MetadataInterface
            {
                return $this->resources[$alias];
            }

            public function getByClass(string $className): MetadataInterface
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }

            public function add(MetadataInterface $metadata): void
            {
                $this->resources[$metadata->getAlias()] = $metadata;
            }

            public function addFromAliasAndConfiguration(string $alias, array $configuration): void
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }

            private function createMetadata(string $name, string $applicationName): MetadataInterface
            {
                return new class ($name, $applicationName) implements MetadataInterface {
                    public string|null $templatesNamespace;

                    /** @var array<string,mixed> */
                    public array $parameters;

                    public function __construct(
                        public string $name,
                        public string $applicationName,
                    ) {
                        $this->parameters = [];
                        $this->templatesNamespace = null;
                    }

                    public string $driver = 'doctrine/orm';

                    public function getAlias(): string
                    {
                        return $this->applicationName . '.' . $this->name;
                    }

                    public function getHumanizedName(): string
                    {
                        return $this->name;
                    }

                    public function getPluralName(): string
                    {
                        return $this->name . 's';
                    }

                    public function getParameter(string $name): mixed
                    {
                        return $this->parameters[$name] ?? null;
                    }

                    public function hasParameter(string $name): bool
                    {
                        return isset($this->parameters[$name]);
                    }

                    public function getAction(string $name): string
                    {
                        throw new \InvalidArgumentException('Not implemented in test.');
                    }

                    public function hasAction(string $name): bool
                    {
                        return false;
                    }

                    public function getClass(string $name): string
                    {
                        throw new \InvalidArgumentException('Not implemented in test.');
                    }

                    public function hasClass(string $name): bool
                    {
                        return false;
                    }

                    public function getServiceId(string $serviceName, string|null $suffix = null): string
                    {
                        return '';
                    }

                    public function getPermissionCode(string $permissionName): string
                    {
                        return '';
                    }
                };
            }
        };

        $command = new DebugResourceCommand($registry);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();

        $this->assertIsInt(strpos($display, 'Alias'));
        $alphaPosition = strpos($display, 'wd.alpha');
        $zetaPosition = strpos($display, 'wd.zeta');
        $this->assertIsInt($alphaPosition);
        $this->assertIsInt($zetaPosition);
        $this->assertLessThan($zetaPosition, $alphaPosition);
    }

    public function testDebugResourceShowsExtendedInformationAndFlattenParameters(): void
    {
        $metadata = new class implements MetadataInterface {
            public string $name = 'customer';
            public string $applicationName = 'wd';
            public string $driver = 'doctrine/orm';
            public string|null $templatesNamespace = 'wd_admin';

            /** @var array<string,mixed> */
            public array $parameters = [
                'templates' => [
                    'show' => '@wd/show.html.twig',
                ],
                'classes' => [
                    'model' => 'App\\Entity\\Customer',
                ],
            ];

            public function getAlias(): string
            {
                return 'wd.customer';
            }

            public function getHumanizedName(): string
            {
                return 'customer';
            }

            public function getPluralName(): string
            {
                return 'customers';
            }

            public function getParameter(string $name): mixed
            {
                return $this->parameters[$name] ?? null;
            }

            public function hasParameter(string $name): bool
            {
                return isset($this->parameters[$name]);
            }

            public function getAction(string $name): string
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }

            public function hasAction(string $name): bool
            {
                return false;
            }

            public function getClass(string $name): string
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }

            public function hasClass(string $name): bool
            {
                return false;
            }

            public function getServiceId(string $serviceName, string|null $suffix = null): string
            {
                return '';
            }

            public function getPermissionCode(string $permissionName): string
            {
                return '';
            }
        };

        $registry = new class ($metadata) implements MetadataRegistryInterface {
            public function __construct(private MetadataInterface $metadata)
            {
            }

            public function getAll(): array
            {
                return [$this->metadata->getAlias() => $this->metadata];
            }

            public function get(string $alias): MetadataInterface
            {
                return $this->metadata;
            }

            public function getByClass(string $className): MetadataInterface
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }

            public function add(MetadataInterface $metadata): void
            {
            }

            public function addFromAliasAndConfiguration(string $alias, array $configuration): void
            {
                throw new \InvalidArgumentException('Not implemented in test.');
            }
        };

        $command = new DebugResourceCommand($registry);

        $tester = new CommandTester($command);
        $tester->execute(['resource' => 'wd.customer']);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('alias', $display);
        $this->assertStringContainsString('wd.customer', $display);
        $this->assertStringContainsString('humanized_name', $display);
        $this->assertStringContainsString('plural_name', $display);
        $this->assertStringContainsString('templates_namespace', $display);
        $this->assertStringContainsString('wd_admin', $display);
        $this->assertStringContainsString('templates.show', $display);
        $this->assertStringContainsString('@wd/show.html.twig', $display);
        $this->assertStringContainsString('classes.model', $display);
        $this->assertStringContainsString('App\\Entity\\Customer', $display);
    }
}
