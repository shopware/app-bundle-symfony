<?php declare(strict_types=1);

namespace Shopware\AppBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ShopwareAppBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        /** @var string $environment */
        $environment = $container->getParameter('kernel.environment');

        $this->buildConfig($container, $environment);
    }

    private function buildConfig(ContainerBuilder $container, string $environment): void
    {
        $locator = new FileLocator('Resources/config');
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $configDir = __DIR__ . '/Resources/config';

        $configLoader->load($configDir . '/{packages}' . '/*{yaml}', 'glob');
        $configLoader->load($configDir . '/{packages}' . $environment . '/*{yaml}', 'glob');
    }
}
