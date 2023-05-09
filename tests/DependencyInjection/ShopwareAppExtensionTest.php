<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\AppBundle\DependencyInjection\ShopwareAppExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ShopwareAppExtensionTest extends TestCase
{
    public function testLoadConfig(): void
    {
        $extension = new ShopwareAppExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        static::assertTrue($container->hasDefinition(ShopRepositoryInterface::class));
    }
}
