<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\AppBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Entity\AbstractShop;
use Shopware\AppBundle\HttpClient\ShopUrlGuardHttpClient;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();

        static::assertInstanceOf(TreeBuilder::class, $treeBuilder);
        static::assertEquals(self::getComparableTreeBuilder(), $treeBuilder);
    }

    public static function getComparableTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_app');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->enumNode('storage')
                ->values(['in-memory', 'doctrine', 'dynamodb', 'auto'])
                ->defaultValue('auto')
            ->end()
            ->arrayNode('doctrine')
                ->children()
                    ->scalarNode('shop_class')
                        ->defaultValue(AbstractShop::class)
                    ->end()
                ->end()
            ->end()
            ->arrayNode('dynamodb')
                ->children()
                    ->scalarNode('table_name')
                        ->defaultValue('shops')
                    ->end()
                ->end()
            ->end()
            ->scalarNode('confirmation_url')
                ->defaultValue('shopware_app_lifecycle_confirm')
            ->end()
            ->scalarNode('name')
                ->defaultValue('TestApp')
            ->end()
            ->scalarNode('secret')
                ->defaultValue('TestSecret')
            ->end()
            ->booleanNode('check_if_shop_url_is_reachable')
                ->defaultFalse()
            ->end()
            ->booleanNode('enforce_double_signature')
                ->defaultFalse()
            ->end()
            ->arrayNode('shop_http_client')
                ->info('The HTTP client the app reaches shops with, through the SDK\'s ClientFactory and the shop URL reachability check.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('base_client')
                        ->info('Service id of the Symfony HTTP client the shop client is built on.')
                        ->defaultValue('http_client')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('url_guard')
                        ->info('Refuses private and reserved addresses and plain http on every redirect hop, and caps redirects and the response size. A shop URL is supplied by whoever installs the app.')
                        ->canBeEnabled()
                        ->children()
                            ->integerNode('max_redirects')
                                ->min(0)
                                ->defaultValue(ShopUrlGuardHttpClient::MAX_REDIRECTS)
                            ->end()
                            ->integerNode('max_response_bytes')
                                ->info('Counts the bytes as received, before decompression, so a compressed response can decode to more.')
                                ->min(1)
                                ->defaultValue(ShopUrlGuardHttpClient::MAX_RESPONSE_BYTES)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function testShopHttpClientDefaultsLeaveTheBaseClientAsItIs(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        static::assertSame([
            'base_client' => 'http_client',
            'url_guard' => [
                'enabled' => false,
                'max_redirects' => 5,
                'max_response_bytes' => 8 * 1024 * 1024,
            ],
        ], $config['shop_http_client']);
    }

    public function testShopHttpClientUrlGuardCanBeEnabledByItsShorthand(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [
            ['shop_http_client' => ['url_guard' => true]],
        ]);

        static::assertTrue($config['shop_http_client']['url_guard']['enabled']);
    }

    /**
     * @param array<string, mixed> $shopHttpClient
     */
    #[DataProvider('invalidShopHttpClientConfigs')]
    public function testShopHttpClientRejectsInvalidValues(array $shopHttpClient): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [['shop_http_client' => $shopHttpClient]]);
    }

    public static function invalidShopHttpClientConfigs(): \Generator
    {
        yield 'empty base client' => [['base_client' => '']];
        yield 'negative redirects' => [['url_guard' => ['max_redirects' => -1]]];
        yield 'no response bytes' => [['url_guard' => ['max_response_bytes' => 0]]];
    }
}
