<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use AsyncAws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBRepository;
use Shopware\App\SDK\Adapter\DynamoDB\DynamoDBShop;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\ArgumentValueResolver\ContextArgumentResolver;
use Shopware\AppBundle\DependencyInjection\AppConfigurationFactory;
use Shopware\AppBundle\DependencyInjection\ShopwareAppExtension;
use Shopware\AppBundle\Entity\AbstractShop;
use Shopware\AppBundle\EventListener\BeforeRegistrationStartsListener;
use Shopware\AppBundle\HttpClient\ShopUrlGuardHttpClient;
use Shopware\AppBundle\HttpClient\ShutdownCancellingHttpClient;
use Shopware\AppBundle\PsrRequestProvider;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\Psr18Client;

class ShopwareAppExtensionTest extends TestCase
{
    public function testDefaultConfigAuto(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));

        static::assertSame(DynamoDBRepository::class, $container->getDefinition(ShopRepositoryInterface::class)->getClass());

        static::assertTrue($container->hasParameter('shopware_app.check_if_shop_url_is_reachable'));
    }

    public function testPsrRequestProviderIsRegistered(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        // ContextArgumentResolver is autowired, so the container only compiles
        // when PsrRequestProvider has a definition of its own.
        static::assertTrue($container->hasDefinition(ContextArgumentResolver::class));
        static::assertTrue($container->hasDefinition(PsrRequestProvider::class));
        static::assertTrue($container->hasDefinition(HttpMessageFactoryInterface::class));

        $factory = $container->getDefinition(PsrRequestProvider::class)->getArgument(0);

        static::assertInstanceOf(Reference::class, $factory);
        static::assertSame(HttpMessageFactoryInterface::class, $factory->__toString());
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
        static::assertCount(5, $container->getDefinition(AppConfigurationFactory::class)->getArguments());

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
        static::assertCount(5, $container->getDefinition(AppConfigurationFactory::class)->getArguments());

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

    public function testSdkAndReachabilityCheckReachShopsThroughTheShopHttpClient(): void
    {
        $container = new ContainerBuilder();
        (new ShopwareAppExtension())->load([], $container);

        $psr18Client = $container->getDefinition(ShopwareAppExtension::SHOP_PSR18_CLIENT);
        static::assertSame(Psr18Client::class, $psr18Client->getClass());
        static::assertEquals([new Reference(ShopwareAppExtension::SHOP_HTTP_CLIENT)], $psr18Client->getArguments());

        static::assertEquals(
            new Reference(ShopwareAppExtension::SHOP_PSR18_CLIENT),
            $container->getDefinition(ClientFactory::class)->getArgument('$client')
        );
        static::assertEquals(
            new Reference(ShopwareAppExtension::SHOP_HTTP_CLIENT),
            $container->getDefinition(BeforeRegistrationStartsListener::class)->getArgument(0)
        );
    }

    public function testShopHttpClientIsTheBaseClientWhenNothingIsConfigured(): void
    {
        $container = new ContainerBuilder();
        (new ShopwareAppExtension())->load([], $container);

        static::assertSame('http_client', (string) $container->getAlias(ShopwareAppExtension::SHOP_HTTP_CLIENT));

        $cancelling = $container->getDefinition('shopware_app.shop_http_client.cancel_on_shutdown');
        static::assertSame(ShutdownCancellingHttpClient::class, $cancelling->getClass());
        static::assertSame([ShopwareAppExtension::SHOP_HTTP_CLIENT, null, 32], $cancelling->getDecoratedService());
        static::assertEquals([new Reference('.inner')], $cancelling->getArguments());

        static::assertFalse($container->hasDefinition('shopware_app.shop_http_client.url_guard'));
    }

    public function testShopHttpClientStacksTheConfiguredDecorators(): void
    {
        $container = new ContainerBuilder();
        (new ShopwareAppExtension())->load([['shop_http_client' => [
            'base_client' => 'shop.base_client',
            'url_guard' => ['enabled' => true, 'max_redirects' => 3, 'max_response_bytes' => 1024],
        ]]], $container);

        static::assertSame('shop.base_client', (string) $container->getAlias(ShopwareAppExtension::SHOP_HTTP_CLIENT));
        static::assertTrue($container->hasDefinition('shopware_app.shop_http_client.cancel_on_shutdown'));

        $guard = $container->getDefinition('shopware_app.shop_http_client.url_guard');
        static::assertSame(ShopUrlGuardHttpClient::class, $guard->getClass());
        static::assertSame([ShopwareAppExtension::SHOP_HTTP_CLIENT, null, 16], $guard->getDecoratedService());
        static::assertEquals([new Reference('.inner'), 3, 1024], $guard->getArguments());
        static::assertSame([['method' => 'reset']], $guard->getTag('kernel.reset'));
    }
}
