<?php declare(strict_types=1);

namespace Shopware\AppBundle\Test\Client;

use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Client\GuzzleClientFactory;
use Shopware\AppBundle\Client\ShopClient;
use Shopware\AppBundle\Shop\ShopEntity;

class GuzzleClientFactoryTest extends TestCase
{
    public function testItReturnsAShopClient(): void
    {
        $clientFactory = new GuzzleClientFactory();

        static::assertInstanceOf(
            ShopClient::class,
            $clientFactory->createClient(
                new ShopEntity('shopId', 'shopUrl', 'secret')
            )
        );
    }
}
