<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\Entity;

use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Entity\AbstractShop;

class AbstractShopTest extends TestCase
{
    public function testMethods(): void
    {
        $shop = new TestShop('1', 'https://test-url.com/', '3');
        $shop->setShopSecret('2');
        $shop->setShopUrl('https://test-url.com/');

        static::assertSame('1', $shop->getShopId());
        static::assertSame('2', $shop->getShopSecret());
        static::assertSame('https://test-url.com', $shop->getShopUrl());
        static::assertFalse($shop->isShopActive());
        $shop->setShopActive(true);
        static::assertTrue($shop->isShopActive());

        $shop->setShopApiCredentials('a', 'b');
        static::assertSame('a', $shop->getShopClientId());
        static::assertSame('b', $shop->getShopClientSecret());
    }

    #[DataProvider('shopValidUrlDataProvider')]
    public function testInvalidUrl(
        string $shopUrl,
        string $expectedUrl
    ): void {
        $shop = new TestShop('shopId', $shopUrl, 'shopSecret');
        static::assertSame($expectedUrl, $shop->getShopUrl());
    }

    public static function shopValidUrlDataProvider(): \Generator
    {
        yield 'Valid URL without trailing slash' => [
            'shopUrl' => 'https://test.com',
            'expectedUrl' => 'https://test.com',
        ];

        yield 'Valid URL with trailing slash' => [
            'shopUrl' => 'https://test.com/',
            'expectedUrl' => 'https://test.com',
        ];

        yield 'Invalid URL with trailing slash' => [
            'shopUrl' => 'https://test.com/test/',
            'expectedUrl' => 'https://test.com/test',
        ];

        yield 'Invalid URL with double slashes' => [
            'shopUrl' => 'https://test.com//test',
            'expectedUrl' => 'https://test.com/test',
        ];


        yield 'Invalid URL with 2 slashes and trailing slash' => [
            'shopUrl' => 'https://test.com//test/',
            'expectedUrl' => 'https://test.com/test',
        ];

        yield 'Invalid URL with 3 slashes and trailing slash' => [
            'shopUrl' => 'https://test.com///test/',
            'expectedUrl' => 'https://test.com/test',
        ];

        yield 'Invalid URL with multiple slashes' => [
            'shopUrl' => 'https://test.com///test/test1//test2',
            'expectedUrl' => 'https://test.com/test/test1/test2',
        ];

        yield 'Invalid URL with multiple slashes and trailing slash' => [
            'shopUrl' => 'https://test.com///test/test1//test2/',
            'expectedUrl' => 'https://test.com/test/test1/test2',
        ];

        yield 'Invalid URL with multiple slashes and multplie trailing slash' => [
            'shopUrl' => 'https://test.com///test/test1//test2//',
            'expectedUrl' => 'https://test.com/test/test1/test2',
        ];
    }
}

class TestShop extends AbstractShop
{
}
