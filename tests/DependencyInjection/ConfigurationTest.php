<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use Shopware\AppBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Entity\AbstractShop;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

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
            ->scalarNode('storage')->defaultValue('doctrine')->end()
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
