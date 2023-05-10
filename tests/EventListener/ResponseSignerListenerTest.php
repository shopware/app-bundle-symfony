<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Shopware\App\SDK\Test\MockShop;
use Shopware\AppBundle\AppRequest;
use Shopware\AppBundle\EventListener\ResponseSignerListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseSignerListenerTest extends TestCase
{
    public function testNormalResponse(): void
    {
        $response = new Response();
        $request = new Request();

        $listener = new ResponseSignerListener();
        $listener(new ResponseEvent(static::createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        static::assertFalse($response->headers->has('shopware-app-signature'));
    }

    public function testNullResponse(): void
    {
        $response = new Response();
        $request = new Request();
        $request->attributes->set(AppRequest::SIGN_RESPONSE, true);
        $request->attributes->set(AppRequest::SHOP_ATTRIBUTE, new MockShop('1', '2', '3'));

        $listener = new ResponseSignerListener();
        $listener(new ResponseEvent(static::createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        static::assertFalse($response->headers->has('shopware-app-signature'));
    }

    public function testFilledResponse(): void
    {
        $response = new Response();
        $response->setContent('test');
        $request = new Request();
        $request->attributes->set(AppRequest::SIGN_RESPONSE, true);
        $request->attributes->set(AppRequest::SHOP_ATTRIBUTE, new MockShop('1', '2', '3'));

        $listener = new ResponseSignerListener();
        $listener(new ResponseEvent(static::createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        static::assertTrue($response->headers->has('shopware-app-signature'));
        static::assertSame('c29f355ed3adf346156cd184d827b62285dfcfa6713f9ad957b44c131ed31613', $response->headers->get('shopware-app-signature'));
    }
}
