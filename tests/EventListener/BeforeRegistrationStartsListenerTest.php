<?php

declare(strict_types=1);

namespace EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Event\BeforeRegistrationStartsEvent;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\AppBundle\EventListener\BeforeRegistrationStartsListener;
use Shopware\AppBundle\Exception\ShopURLIsNotReachableException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(BeforeRegistrationStartsListener::class)]
final class BeforeRegistrationStartsListenerTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testListenerMustReturnBecauseTheCheckIsSetToFalseInBundleConfiguration(): void
    {
        $shop = $this->createMock(ShopInterface::class);
        $shop
            ->expects(self::never())
            ->method('getShopUrl');

        $this->httpClient
            ->expects(self::never())
            ->method('request');

        $listener = new BeforeRegistrationStartsListener(
            $this->httpClient,
            false
        );

        $listener->__invoke(
            new BeforeRegistrationStartsEvent(
                $this->createMock(RequestInterface::class),
                $shop
            )
        );
    }

    public function testListenerMustBeExecutedWithoutErrorsIfTheCheckIsSetToTrueInConfiguration(): void
    {
        $shop = $this->createMock(ShopInterface::class);
        $shop
            ->expects(self::once())
            ->method('getShopUrl')
            ->willReturn('https://shop-url.com');

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('HEAD', 'https://shop-url.com/api/_info/config', [
                'timeout' => 10,
                'max_redirects' => 0,
            ]);

        $listener = new BeforeRegistrationStartsListener(
            $this->httpClient,
            true
        );

        $listener->__invoke(
            new BeforeRegistrationStartsEvent(
                $this->createMock(RequestInterface::class),
                $shop
            )
        );
    }

    public function testListenerMustThrowExceptionBecauseTheShopURLIsNotReachable(): void
    {
        $this->expectException(ShopURLIsNotReachableException::class);
        $this->expectExceptionMessage('Shop URL "https://shop-url.com" is not reachable from the application server.');

        $shop = $this->createMock(ShopInterface::class);
        $shop
            ->expects(self::exactly(2))
            ->method('getShopUrl')
            ->willReturn('https://shop-url.com');

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with('HEAD', 'https://shop-url.com/api/_info/config', [
                'timeout' => 10,
                'max_redirects' => 0,
            ])
            ->willThrowException(new \Exception('Shop url is not reachable'));

        $listener = new BeforeRegistrationStartsListener(
            $this->httpClient,
            true
        );

        $listener->__invoke(
            new BeforeRegistrationStartsEvent(
                $this->createMock(RequestInterface::class),
                $shop
            )
        );
    }
}
