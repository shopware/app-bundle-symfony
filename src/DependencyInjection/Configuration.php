<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_app');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('shop_class')
                ->defaultValue(AbstractShop::class)
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
