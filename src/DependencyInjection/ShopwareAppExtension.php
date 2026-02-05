<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use AsyncAws\DynamoDb\DynamoDbClient;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBRepository;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class ShopwareAppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.php');

        $storage = $config['storage'];

        if ($storage === 'auto') {
            // @infection-ignore-all
            $storage = match (true) {
                ContainerBuilder::willBeAvailable('async-aws/dynamo-db', DynamoDbClient::class, ['async-aws/async-aws-bundle']) => 'dynamodb',
                ContainerBuilder::willBeAvailable('doctrine/orm', DoctrineBundle::class, ['doctrine/doctrine-bundle']) => 'doctrine',
                default => 'in-memory',
            };
        }

        if ($storage === 'dynamodb') {
            $service = new Definition(DynamoDBRepository::class);
            $service->setArgument(0, new Reference(DynamoDbClient::class));
            $service->setArgument(1, $config['dynamodb']['table_name'] ?? 'shops');
            $container->setDefinition(ShopRepositoryInterface::class, $service);
        } elseif ($storage === 'doctrine') {
            $container->getDefinition(ShopRepositoryInterface::class)
                ->replaceArgument(0, $config['doctrine']['shop_class'] ?? AbstractShop::class);
        } else {
            $container->setDefinition(ShopRepositoryInterface::class, new Definition(MockShopRepository::class));
        }

        $container->getDefinition(AppConfigurationFactory::class)
            ->replaceArgument(0, $config['name'])
            ->replaceArgument(1, $config['secret'])
            ->replaceArgument(2, $config['confirmation_url'])
            ->replaceArgument(4, $config['enforce_double_signature']);

        $container->setParameter(
            sprintf('%s.check_if_shop_url_is_reachable', Configuration::EXTENSION_ALIAS),
            $config['check_if_shop_url_is_reachable']
        );
    }
}
