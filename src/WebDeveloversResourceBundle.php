<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceActionPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceIndexPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourceRoutesPass;
use WebDevelovers\ResourceBundle\DependencyInjection\Compiler\RegisterResourcesPass;

final class WebDeveloversResourceBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new RegisterResourcesPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            200,
        );

        $container->addCompilerPass(
            new RegisterResourceActionPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            100,
        );

        $container->addCompilerPass(
            new RegisterResourceIndexPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            90,
        );

        $container->addCompilerPass(
            new RegisterResourceRoutesPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            80,
        );
    }
}

