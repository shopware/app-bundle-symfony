<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\ArgumentValueResolver;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shopware\App\SDK\Authentication\DualSignatureRequestVerifier;
use Shopware\App\SDK\Context\ActionButton\ActionButtonAction;
use Shopware\App\SDK\Context\ContextResolver;
use Shopware\App\SDK\Context\Gateway\Checkout\CheckoutGatewayAction;
use Shopware\App\SDK\Context\Gateway\Context\ContextGatewayAction;
use Shopware\App\SDK\Context\Gateway\InAppFeatures\FilterAction;
use Shopware\App\SDK\Context\Module\ModuleAction;
use Shopware\App\SDK\Context\Payment\PaymentCaptureAction;
use Shopware\App\SDK\Context\Payment\PaymentFinalizeAction;
use Shopware\App\SDK\Context\Payment\PaymentPayAction;
use Shopware\App\SDK\Context\Payment\PaymentRecurringAction;
use Shopware\App\SDK\Context\Payment\PaymentValidateAction;
use Shopware\App\SDK\Context\Payment\RefundAction;
use Shopware\App\SDK\Context\Storefront\StorefrontAction;
use Shopware\App\SDK\Context\TaxProvider\TaxProviderAction;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\App\SDK\Test\MockShop;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\AppBundle\AppRequest;
use Shopware\AppBundle\ArgumentValueResolver\ContextArgumentResolver;
use Shopware\AppBundle\PsrRequestProvider;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ContextArgumentResolverTest extends TestCase
{
    public static function provideTypes(): \Generator
    {
        yield 'invalid' => [
            Request::class,
            false
        ];

        yield 'psr request' => [
            RequestInterface::class,
            true,
        ];

        yield 'shop request' => [
            ShopInterface::class,
            true,
        ];
    }

    #[DataProvider('provideTypes')]
    public function testSupportedTypes(string $type, bool $expected): void
    {
        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            new PsrRequestProvider(static::createMock(HttpMessageFactoryInterface::class))
        );

        static::assertSame($expected, $resolver->supports(new Request(), new ArgumentMetadata('test', $type, false, false, null)));
    }

    public function testResolveRequest(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)))[0];

        static::assertInstanceOf(RequestInterface::class, $result);
        static::assertSame('test', $result->getAttribute('shopware-app-context'));
    }

    public function testResolveEmptyTypeThrowsException(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        static::assertEmpty(iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', null, false, false, null))));
    }

    public function testResolveShop(): void
    {
        $request = $this->getRequest();

        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn(static::createMock(ShopInterface::class));
        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            $shopResolver,
            $this->getPsrRequestProvider()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ShopInterface::class, false, false, null)))[0];

        static::assertInstanceOf(ShopInterface::class, $result);
    }

    public function testRequestIsConverted(): void
    {
        $nonPsrRequest = new Request(['foo' => 'bar']);

        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => $nonPsrRequest]);
        $request->headers->set('HOST', 'localhost');

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        $result = \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)));

        static::assertIsArray($result);
        static::assertCount(1, $result);

        $result = $result[0];

        static::assertInstanceOf(ServerRequest::class, $result);
        static::assertSame($result->getAttribute(AppRequest::PSR_REQUEST_ATTRIBUTE), $nonPsrRequest);
    }

    public function testRequestConversionWithWrongData(): void
    {
        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => false]);
        $request->headers->set('HOST', 'localhost');

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)));

        static::assertInstanceOf(Request::class, $request);
        static::assertInstanceOf(ServerRequest::class, $request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public function testNoConversionAppliedWithPsrRequest(): void
    {
        $psrRequest = new ServerRequest('GET', 'http://localhost');

        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => $psrRequest]);

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)));

        static::assertInstanceOf(Request::class, $request);
        static::assertSame($request->attributes->get(AppRequest::PSR_REQUEST_ATTRIBUTE), $psrRequest);
    }

    public function testShopResolved(): void
    {
        $repository = new MockShopRepository();
        $shopResolver = new ShopResolver($repository, static::createMock(DualSignatureRequestVerifier::class));

        $shop = new MockShop('123', 'http://example.com', 'secret');

        $repository->createShop($shop);

        $psrRequest = new ServerRequest(
            'POST',
            'http://localhost',
            ['Content-Type' => 'application/json'],
            \json_encode(['source' => ['shopId' => '123']])
        );

        $request = new Request(attributes: [AppRequest::PSR_REQUEST_ATTRIBUTE => $psrRequest]);

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            $shopResolver,
            new PsrRequestProvider(static::createMock(HttpMessageFactoryInterface::class))
        );

        \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ShopInterface::class, false, false, null)));

        static::assertSame($shop, $request->attributes->get(AppRequest::SHOP_ATTRIBUTE));
    }

    public function testExistingShopIsNotResolved(): void
    {
        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver
            ->expects(static::never())
            ->method('resolveShop');

        $factory = static::createMock(HttpMessageFactoryInterface::class);
        $factory->method('createRequest')->willReturn(new ServerRequest('GET', 'http://localhost'));

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            $shopResolver,
            new PsrRequestProvider($factory)
        );

        $shop = new MockShop('123', 'http://example.com', 'secret');
        $request = new Request(attributes: [AppRequest::SHOP_ATTRIBUTE => $shop]);

        $result = \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ShopInterface::class, false, false, null)));

        static::assertIsArray($result);
        static::assertCount(1, $result);

        $result = $result[0];

        static::assertSame($shop, $result);
    }

    public static function provideActions(): \Generator
    {
        yield [WebhookAction::class];
        yield [ModuleAction::class];
        yield [ActionButtonAction::class];
        yield [TaxProviderAction::class];
        yield [PaymentPayAction::class];
        yield [PaymentFinalizeAction::class];
        yield [PaymentValidateAction::class];
        yield [PaymentCaptureAction::class];
        yield [PaymentRecurringAction::class];
        yield [RefundAction::class];
        yield [StorefrontAction::class];
        yield [CheckoutGatewayAction::class];
        yield [ContextGatewayAction::class];
        yield [FilterAction::class];
    }

    #[DataProvider('provideActions')]
    public function testResolvingOfActions(string $action): void
    {
        $request = $this->getRequest();

        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn(static::createMock(ShopInterface::class));
        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            $shopResolver,
            $this->getPsrRequestProvider()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', $action, false, false, null)))[0];

        static::assertInstanceOf($action, $result);
    }

    public static function provideSigningActions(): \Generator
    {
        yield [ActionButtonAction::class, true];
        yield [TaxProviderAction::class, true];
        yield [PaymentPayAction::class, true];
        yield [PaymentFinalizeAction::class, true];
        yield [PaymentValidateAction::class, true];
        yield [PaymentCaptureAction::class, true];
        yield [PaymentRecurringAction::class, true];
        yield [RefundAction::class, true];
        yield [WebhookAction::class, false];
        yield [ModuleAction::class, false];
        yield [StorefrontAction::class, false];
        yield [CheckoutGatewayAction::class, true];
        yield [ContextGatewayAction::class, true];
    }

    #[DataProvider('provideSigningActions')]
    public function testSigningActions(string $action, bool $requiresSigning): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', $action, false, false, null)));

        if ($requiresSigning) {
            static::assertTrue($request->attributes->get(AppRequest::SIGN_RESPONSE));
        } else {
            static::assertFalse($request->attributes->has(AppRequest::SIGN_RESPONSE));
        }
    }

    public function testMalformedJsonBodyIsRejected(): void
    {
        $request = Request::create('http://localhost/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"source":');

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        $this->expectException(JsonException::class);

        \iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)));
    }

    public function testUnsupportedArgumentDoesNotTouchTheRequest(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            new PsrRequestProvider(static::createMock(HttpMessageFactoryInterface::class))
        );

        static::assertEmpty(\iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', Request::class, false, false, null))));
        static::assertFalse($request->attributes->has(AppRequest::PSR_REQUEST_ATTRIBUTE));
    }

    public function testPsrRequestIsSharedBetweenResolvedArguments(): void
    {
        $request = $this->getRequest();

        $shopResolver = static::createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn(static::createMock(ShopInterface::class));

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            $shopResolver,
            $this->getPsrRequestProvider()
        );

        $first = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)))[0];

        iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ShopInterface::class, false, false, null)));

        $second = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ServerRequestInterface::class, false, false, null)))[0];

        static::assertSame($first, $second);
    }

    public function testIgnoresUnknownArgumentTypes(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            static::createMock(ContextResolver::class),
            static::createMock(ShopResolver::class),
            $this->getPsrRequestProvider()
        );

        static::assertEmpty(\iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', \stdClass::class, false, false, null))));
    }

    public function getRequest(): Request
    {
        $request = new Request();
        $request->headers->set('HOST', 'localhost');
        $request->attributes->set('shopware-app-context', 'test');
        return $request;
    }

    public function getPsrRequestProvider(): PsrRequestProvider
    {
        $psr17Factory = new Psr17Factory();

        return new PsrRequestProvider(new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory));
    }
}
