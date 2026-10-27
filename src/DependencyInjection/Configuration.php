<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Shopware\AppBundle\Entity\AbstractShop;
use Shopware\AppBundle\HttpClient\ShopUrlGuardHttpClient;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const EXTENSION_ALIAS = 'shopware_app';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::EXTENSION_ALIAS);

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
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
                                ->min(1)
                                ->defaultValue(ShopUrlGuardHttpClient::MAX_RESPONSE_BYTES)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
