<?php declare(strict_types=1);

namespace Shopware\AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shopware_app');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('metadata')
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                            ->end()
                        ->arrayNode('label')
                            ->isRequired()
                            ->normalizeKeys(false)
                            ->ignoreExtraKeys(false)
                            ->children()
                                ->scalarNode('default')
                                    ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                        ->arrayNode('description')
                            ->isRequired()
                            ->normalizeKeys(false)
                            ->ignoreExtraKeys(false)
                            ->children()
                                ->scalarNode('default')
                                    ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                        ->scalarNode('author')
                            ->isRequired()
                            ->end()
                        ->scalarNode('copyright')
                            ->isRequired()
                            ->end()
                        ->scalarNode('version')
                            ->isRequired()
                            ->end()
                        ->scalarNode('license')
                            ->isRequired()
                            ->end()
                        ->scalarNode('icon')
                            ->end()
                        ->scalarNode('privacy')
                            ->end()
                        ->arrayNode('privacyPolicyExtensions')
                            ->normalizeKeys(false)
                            ->ignoreExtraKeys(false)
                            ->children()
                                ->scalarNode('default')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('setup')
                    ->children()
                        ->scalarNode('secret')
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
