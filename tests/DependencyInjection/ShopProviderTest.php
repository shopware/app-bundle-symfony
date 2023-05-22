<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use Nyholm\Psr7\Factory\Psr17Factory;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\AppBundle\DependencyInjection\ShopProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ShopProviderTest extends TestCase
{
    public function testProvideShop(): void
    {
        $requestStack = $this->getRequestStack();

        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn(static::createMock(ShopInterface::class));
        $shopProvider = new ShopProvider(
            $requestStack,
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = $shopProvider->provide();

        static::assertInstanceOf(ShopInterface::class, $result);
    }

    public function testDoesNotProvideShopWhenNoRequest(): void
    {
        // create empty request stack and don't push a request in
        $requestStack = new RequestStack();

        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn(static::createMock(ShopInterface::class));
        $shopProvider = new ShopProvider(
            $requestStack,
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = $shopProvider->provide();

        static::assertNull($result);
    }

    public function getRequestStack(): RequestStack
    {
        $request = new Request();
        $request->headers->set('HOST', 'localhost');
        $request->attributes->set('shopware-app-context', 'test');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    public function getPsrHttpFactory(): PsrHttpFactory
    {
        return new PsrHttpFactory(new Psr17Factory(), new Psr17Factory(), new Psr17Factory(), new Psr17Factory());
    }
}
