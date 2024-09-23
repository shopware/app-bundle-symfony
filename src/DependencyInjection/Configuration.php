<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_app');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode->children()
            ->enumNode('storage')
                ->values(['in-memory', 'doctrine', 'dynamodb'])
                ->defaultValue('in-memory')
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
                ->end();

        return $treeBuilder;
    }
}
