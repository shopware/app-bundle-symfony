<?php declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ShopwareAppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $this->addConfigToParameters($container, $this->getAlias(), $config);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.xml');
    }

    private function addConfigToParameters(ContainerBuilder $container, string $prefix, array $options): void
    {
        foreach ($options as $key => $option) {
            $key = "{$prefix}.{$key}";

            // set metadata and permissions at once
            if ($key === 'shopware_app.metadata' || $key === 'shopware_app.permissions') {
                $container->setParameter($key, $option);

                continue;
            }

            // add values from arrays in single parameters
            if (\is_array($option)) {
                $this->addConfigToParameters($container, $key, $option);

                continue;
            }

            // set scalar values
            $container->setParameter($key, $option);
        }
    }
}
