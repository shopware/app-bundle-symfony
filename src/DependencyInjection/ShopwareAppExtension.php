<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\AppBundle\Registration\AppConfigurationFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ShopwareAppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.xml');

        $container->getDefinition(ShopRepositoryInterface::class)
            ->replaceArgument(0, $config['shop_class']);

        $container->getDefinition(AppConfigurationFactory::class)
            ->replaceArgument(0, $config['name'])
            ->replaceArgument(1, $config['secret'])
            ->replaceArgument(2, $config['confirmation_url']);
    }
}
