<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Authentication\RequestVerifier;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\App\SDK\Test\MockShop;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\AppRequest;
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

        $currentRequest = $requestStack->getCurrentRequest();

        static::assertInstanceOf(ShopInterface::class, $currentRequest->attributes->get(AppRequest::SHOP_ATTRIBUTE));
        static::assertInstanceOf(RequestInterface::class, $currentRequest->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
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

    public function testExistingShopIsNotResolved(): void
    {
        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver
            ->expects(static::never())
            ->method('resolveShop');

        $requestStack = new RequestStack();

        $shop = new MockShop('123', 'https://example.com', 'secret');

        $request = new Request(attributes: [AppRequest::SHOP_ATTRIBUTE => $shop]);
        $request->headers->set('HOST', 'localhost');
        $request->attributes->set('shopware-app-context', 'test');

        $requestStack->push($request);

        $shopProvider = new ShopProvider(
            $requestStack,
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = $shopProvider->provide();

        static::assertInstanceOf(ShopInterface::class, $result);
    }

    public function testExistingPsrRequestIsNotConverted(): void
    {
        $repository = new MockShopRepository();
        $shopResolver = new ShopResolver($repository, static::createMock(RequestVerifier::class));

        $shop = new MockShop('123', 'https://example.com', 'secret');

        $repository->createShop($shop);

        $psrRequest = new \Nyholm\Psr7\Request(
            'POST',
            'https://localhost',
            ['Content-Type' => 'application/json'],
            \json_encode(['source' => ['shopId' => '123']])
        );

        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => $psrRequest]);
        $request->headers->set('HOST', 'localhost');
        $request->attributes->set('shopware-app-context', 'test');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $shopProvider = new ShopProvider(
            $requestStack,
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = $shopProvider->provide();

        static::assertSame($shop, $result);
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
