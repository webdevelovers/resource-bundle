<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceRoutesPass;
use WebDevelovers\ResourceBundle\Routing\AutoResourceRouteLoader;
use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

use function sys_get_temp_dir;
use function uniqid;
use function mkdir;
use function file_put_contents;
use function unlink;
use function rmdir;

final class RegisterResourceRoutesPassTest extends TestCase
{
    public function testProcessWithNoRouteDirectorySetsEmptyConfigurations(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir() . '/wd_test_' . uniqid());

        $pass = new RegisterResourceRoutesPass();
        $pass->process($container);

        self::assertTrue($container->hasParameter('wd.resource_routes'));
        self::assertSame([], $container->getParameter('wd.resource_routes'));
    }

    public function testProcessCollectsRoutesFromClassImplementingInterface(): void
    {
        $tmpDir = sys_get_temp_dir() . '/wd_test_' . uniqid();
        $routesDir = $tmpDir . '/src/Resource/Route';
        mkdir($routesDir, 0777, true);

        $className = 'DummyProductRoute_' . uniqid();
        $classFile = $routesDir . '/' . $className . '.php';

        $code = <<<PHP
<?php

namespace App\Resource\Route;

use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

final class {$className} implements ResourceRouteInterface
{
    public static function config(): array
    {
        return [
            'alias' => 'app.product',
            'path' => 'catalog/products',
        ];
    }
}
PHP;
        file_put_contents($classFile, $code);
        require_once $classFile;

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $tmpDir);

        $pass = new RegisterResourceRoutesPass();
        $pass->process($container);

        self::assertSame([[
            'alias' => 'app.product',
            'path' => 'catalog/products',
        ]], $container->getParameter('wd.resource_routes'));

        unlink($classFile);
        rmdir($routesDir);
        rmdir($tmpDir . '/src/Resource');
        rmdir($tmpDir . '/src');
        rmdir($tmpDir);
    }

    public function testProcessCollectsMultipleConfigurationsFromSingleRouteClass(): void
    {
        $tmpDir = sys_get_temp_dir() . '/wd_test_' . uniqid();
        $routesDir = $tmpDir . '/src/Resource/Route';
        mkdir($routesDir, 0777, true);

        $className = 'DummyMultiProductRoute_' . uniqid();
        $classFile = $routesDir . '/' . $className . '.php';

        $code = <<<PHP
<?php

namespace App\Resource\Route;

use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

final class {$className} implements ResourceRouteInterface
{
    public static function config(): array
    {
        return [
            [
                'alias' => 'app.product',
                'only' => ['index', 'show'],
                'section' => 'public',
            ],
            [
                'alias' => 'app.product',
                'only' => ['create', 'update'],
                'section' => 'admin',
            ],
        ];
    }
}
PHP;
        file_put_contents($classFile, $code);
        require_once $classFile;

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $tmpDir);

        $pass = new RegisterResourceRoutesPass();
        $pass->process($container);

        self::assertSame([
            [
                'alias' => 'app.product',
                'only' => ['index', 'show'],
                'section' => 'public',
            ],
            [
                'alias' => 'app.product',
                'only' => ['create', 'update'],
                'section' => 'admin',
            ],
        ], $container->getParameter('wd.resource_routes'));

        unlink($classFile);
        rmdir($routesDir);
        rmdir($tmpDir . '/src/Resource');
        rmdir($tmpDir . '/src');
        rmdir($tmpDir);
    }

    public function testProcessRegistersAutoResourceRouteLoaderWhenRoutingLoaderExists(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir() . '/wd_test_' . uniqid());
        $container->setDefinition('routing.loader', new Definition(LoaderInterface::class));

        $pass = new RegisterResourceRoutesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition(AutoResourceRouteLoader::class));
        $definition = $container->getDefinition(AutoResourceRouteLoader::class);
        self::assertSame(['routing.loader', null, 0], $definition->getDecoratedService());
    }
}
