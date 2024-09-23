<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use AsyncAws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBRepository;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBShop;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\DependencyInjection\AppConfigurationFactory;
use Shopware\AppBundle\DependencyInjection\ShopwareAppExtension;
use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShopwareAppExtensionTest extends TestCase
{
    public function testDefaultConfigAuto(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));

        static::assertSame(DynamoDBRepository::class, $container->getDefinition(ShopRepositoryInterface::class)->getClass());
    }

    public function testDefaultInMemory(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load(['my_bundle' => ['storage' => 'in-memory']], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));

        static::assertSame(MockShopRepository::class, $container->getDefinition(ShopRepositoryInterface::class)->getClass());
    }

    public function testLoadConfig(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load(['my_bundle' => ['storage' => 'doctrine']], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));
        static::assertTrue($container->hasDefinition(AppConfigurationFactory::class));

        static::assertCount(2, $container->getDefinition(ShopRepositoryInterface::class)->getArguments());
        static::assertCount(4, $container->getDefinition(AppConfigurationFactory::class)->getArguments());

        $shopClass = $container->getDefinition(ShopRepositoryInterface::class)->getArgument(0);

        static::assertSame(AbstractShop::class, $shopClass);

        $name = $container->getDefinition(AppConfigurationFactory::class)->getArgument(0);
        $secret = $container->getDefinition(AppConfigurationFactory::class)->getArgument(1);
        $confirmationUrl = $container->getDefinition(AppConfigurationFactory::class)->getArgument(2);

        static::assertSame('TestApp', $name);
        static::assertSame('TestSecret', $secret);
        static::assertSame('shopware_app_lifecycle_confirm', $confirmationUrl);
    }

    public function testLoadConfigOverwriteShopClass(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load(['my_bundle' => ['storage' => 'doctrine', 'doctrine' => ['shop_class' => DynamoDBShop::class]]], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));
        static::assertTrue($container->hasDefinition(AppConfigurationFactory::class));

        static::assertCount(2, $container->getDefinition(ShopRepositoryInterface::class)->getArguments());
        static::assertCount(4, $container->getDefinition(AppConfigurationFactory::class)->getArguments());

        $shopClass = $container->getDefinition(ShopRepositoryInterface::class)->getArgument(0);

        static::assertSame(DynamoDBShop::class, $shopClass);

        $name = $container->getDefinition(AppConfigurationFactory::class)->getArgument(0);
        $secret = $container->getDefinition(AppConfigurationFactory::class)->getArgument(1);
        $confirmationUrl = $container->getDefinition(AppConfigurationFactory::class)->getArgument(2);

        static::assertSame('TestApp', $name);
        static::assertSame('TestSecret', $secret);
        static::assertSame('shopware_app_lifecycle_confirm', $confirmationUrl);
    }

    public function testLoadUseDynamoDB(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load(['my_bundle' => ['storage' => 'dynamodb']], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));
        static::assertTrue($container->hasDefinition(AppConfigurationFactory::class));

        static::assertCount(2, $container->getDefinition(ShopRepositoryInterface::class)->getArguments());

        $shopClass = $container->getDefinition(ShopRepositoryInterface::class)->getArgument(1);

        static::assertSame('shops', $shopClass);
    }

    public function testLoadUseDynamoDBOverwriteTableName(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load(['my_bundle' => ['storage' => 'dynamodb', 'dynamodb' => ['table_name' => 'foo']]], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));
        static::assertTrue($container->hasDefinition(AppConfigurationFactory::class));

        static::assertCount(2, $container->getDefinition(ShopRepositoryInterface::class)->getArguments());

        $client = $container->getDefinition(ShopRepositoryInterface::class)->getArgument(0);
        static::assertInstanceOf(Reference::class, $client);

        static::assertSame(DynamoDbClient::class, $client->__toString());

        $shopClass = $container->getDefinition(ShopRepositoryInterface::class)->getArgument(1);

        static::assertSame('foo', $shopClass);
    }
}
