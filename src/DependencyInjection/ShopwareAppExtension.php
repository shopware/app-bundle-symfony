<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use AsyncAws\DynamoDb\DynamoDbClient;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBRepository;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class ShopwareAppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.xml');

        if ($config['storage'] === 'dynamodb') {
            $service = new Definition(DynamoDBRepository::class);
            $service->setArgument(0, new Reference(DynamoDbClient::class));
            $service->setArgument(1, $config['dynamodb']['table_name'] ?? 'shops');
            $container->setDefinition(ShopRepositoryInterface::class, $service);
        } elseif ($config['storage'] === 'doctrine') {
            $container->getDefinition(ShopRepositoryInterface::class)
                ->replaceArgument(0, $config['doctrine']['shop_class'] ?? AbstractShop::class);
        } else {
            $container->setDefinition(ShopRepositoryInterface::class, new Definition(MockShopRepository::class));
        }

        $container->getDefinition(AppConfigurationFactory::class)
            ->replaceArgument(0, $config['name'])
            ->replaceArgument(1, $config['secret'])
            ->replaceArgument(2, $config['confirmation_url']);
    }
}
