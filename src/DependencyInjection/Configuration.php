<?php

declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

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
                ->isRequired()
                ->end()
            ->scalarNode('confirmation_url')
                ->isRequired()
                ->end()
            ->scalarNode('name')
                ->isRequired()
                ->end()
            ->scalarNode('secret')
                ->end();

        return $treeBuilder;
    }
}
