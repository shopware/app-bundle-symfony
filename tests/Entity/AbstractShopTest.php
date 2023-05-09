<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Entity;

use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Entity\AbstractShop;

class AbstractShopTest extends TestCase
{
    public function testMethods(): void
    {
        $shop = new TestShop('1', '2', '3');
        $shop->setShopSecret('2');
        $shop->setShopUrl('3');

        static::assertSame('1', $shop->getShopId());
        static::assertSame('2', $shop->getShopSecret());
        static::assertSame('3', $shop->getShopUrl());
        static::assertFalse($shop->isShopActive());
        $shop->setShopActive(true);
        static::assertTrue($shop->isShopActive());

        $shop->setShopApiCredentials('a', 'b');
        static::assertSame('a', $shop->getShopClientId());
        static::assertSame('b', $shop->getShopClientSecret());
    }
}

class TestShop extends AbstractShop
{
}
