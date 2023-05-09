<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\ArgumentValueResolver;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Context\ActionButton\ActionButtonAction;
use Shopware\App\SDK\Context\ContextResolver;
use Shopware\App\SDK\Context\Module\ModuleAction;
use Shopware\App\SDK\Context\Payment\PaymentCaptureAction;
use Shopware\App\SDK\Context\Payment\PaymentFinalizeAction;
use Shopware\App\SDK\Context\Payment\PaymentPayAction;
use Shopware\App\SDK\Context\Payment\PaymentValidateAction;
use Shopware\App\SDK\Context\Payment\RefundAction;
use Shopware\App\SDK\Context\TaxProvider\TaxProviderAction;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\AppBundle\ArgumentValueResolver\ContextArgumentResolver;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
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
            $this->createMock(ContextResolver::class),
            $this->createMock(ShopResolver::class),
            $this->createMock(HttpMessageFactoryInterface::class)
        );

        static::assertSame($expected, $resolver->supports(new Request(), new ArgumentMetadata('test', $type, false, false, null)));
    }

    public function testResolveRequest(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            $this->createMock(ContextResolver::class),
            $this->createMock(ShopResolver::class),
            $this->getPsrHttpFactory()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', RequestInterface::class, false, false, null)))[0];

        static::assertInstanceOf(RequestInterface::class, $result);
        static::assertSame('test', $result->getAttribute('shopware-app-context'));
    }

    public function testResolveEmptyTypeThrowsException(): void
    {
        $request = $this->getRequest();

        $resolver = new ContextArgumentResolver(
            $this->createMock(ContextResolver::class),
            $this->createMock(ShopResolver::class),
            $this->getPsrHttpFactory()
        );

        static::assertEmpty(iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', null, false, false, null))));
    }

    public function testResolveShop(): void
    {
        $request = $this->getRequest();

        $shopResolver = $this->createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn($this->createMock(ShopInterface::class));
        $resolver = new ContextArgumentResolver(
            $this->createMock(ContextResolver::class),
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', ShopInterface::class, false, false, null)))[0];

        static::assertInstanceOf(ShopInterface::class, $result);
    }

    public static function provideActions(): \Generator
    {
        yield [
            WebhookAction::class
        ];

        yield [
            ModuleAction::class
        ];

        yield [
            ActionButtonAction::class
        ];

        yield [
            TaxProviderAction::class
        ];

        yield [
            PaymentPayAction::class
        ];

        yield [
            PaymentFinalizeAction::class
        ];

        yield [
            PaymentValidateAction::class
        ];

        yield [
            PaymentCaptureAction::class
        ];

        yield [
            RefundAction::class
        ];
    }

    #[DataProvider('provideActions')]
    public function testResolvingOfActions(string $action): void
    {
        $request = $this->getRequest();

        $shopResolver = $this->createMock(ShopResolver::class);
        $shopResolver->method('resolveShop')->willReturn($this->createMock(ShopInterface::class));
        $resolver = new ContextArgumentResolver(
            $this->createMock(ContextResolver::class),
            $shopResolver,
            $this->getPsrHttpFactory()
        );

        $result = iterator_to_array($resolver->resolve($request, new ArgumentMetadata('test', $action, false, false, null)))[0];

        static::assertInstanceOf($action, $result);
    }

    public function getRequest(): Request
    {
        $request = new Request();
        $request->headers->set('HOST', 'localhost');
        $request->attributes->set('shopware-app-context', 'test');
        return $request;
    }

    public function getPsrHttpFactory(): PsrHttpFactory
    {
        return new PsrHttpFactory(new Psr17Factory(), new Psr17Factory(), new Psr17Factory(), new Psr17Factory());
    }
}
