<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\DependencyInjection;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Shopware\App\SDK\Event\BeforeRegistrationStartsEvent;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Test\MockShop;
use Shopware\AppBundle\Controller\LifecycleController;
use Shopware\AppBundle\Controller\WebhookController;
use Shopware\AppBundle\DependencyInjection\ShopwareAppExtension;
use Shopware\AppBundle\EventListener\BeforeRegistrationStartsListener;
use Shopware\AppBundle\HttpClient\DefaultsRestoringHttpClient;
use Shopware\AppBundle\Exception\ShopRequestRefusedException;
use Shopware\AppBundle\Exception\ShopURLIsNotReachableException;
use Shopware\AppBundle\HttpClient\ShopUrlGuardHttpClient;
use Shopware\AppBundle\HttpClient\ShutdownCancellingHttpClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The shop client as a compiled container builds it.
 */
#[CoversClass(ShopwareAppExtension::class)]
final class ShopHttpClientWiringTest extends TestCase
{
    private static ?HttpClientInterface $baseClient = null;

    protected function tearDown(): void
    {
        self::$baseClient = null;
    }

    /**
     * Factory of the `http_client` service, which cannot be synthetic: those cannot be decorated.
     */
    public static function baseClient(): HttpClientInterface
    {
        return self::$baseClient ??= new MockHttpClient(new MockResponse('{}'));
    }

    /**
     * The cancelling innermost, beneath every layer that adds a progress callback.
     */
    public function testStacksTheDecoratorsAroundTheBaseClient(): void
    {
        $container = $this->compile(['url_guard' => true]);

        $guard = $container->get('test.shop_http_client');
        static::assertInstanceOf(ShopUrlGuardHttpClient::class, $guard);
        $noPrivateNetwork = self::innerClient($guard);
        static::assertInstanceOf(NoPrivateNetworkHttpClient::class, $noPrivateNetwork);
        $defaultsRestoring = self::innerClient($noPrivateNetwork);
        static::assertInstanceOf(DefaultsRestoringHttpClient::class, $defaultsRestoring);
        $cancelling = self::innerClient($defaultsRestoring);
        static::assertInstanceOf(ShutdownCancellingHttpClient::class, $cancelling);
        static::assertInstanceOf(MockHttpClient::class, self::innerClient($cancelling));
    }

    /**
     * As `framework.http_client.default_options` sets them.
     */
    public function testDefaultOptionsOfTheBaseClientReachARequestThroughTheUrlGuard(): void
    {
        $sent = [];
        $container = $this->compile(
            ['url_guard' => true],
            (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
                $sent = $options;

                return new MockResponse('{}');
            }))->withOptions(['max_duration' => 7])
        );

        $client = $container->get('test.shop_http_client');
        static::assertInstanceOf(HttpClientInterface::class, $client);
        $client->request('GET', 'https://8.8.8.8/api/_info/config')->getContent();

        static::assertSame(7.0, $sent['max_duration']);
    }

    /**
     * The token request carries the shop's client secret, so it is the first request that must not go out.
     */
    public function testSdkClientIsRefusedAPlainHttpShop(): void
    {
        $base = new MockHttpClient(new MockResponse('{}'));
        $factory = $this->compile(['url_guard' => true], $base)->get('test.client_factory');
        static::assertInstanceOf(ClientFactory::class, $factory);
        $shop = new MockShop('shop-id', 'http://8.8.8.8', 'shop-secret', true, 'client-id', 'client-secret');

        try {
            $factory->createClient($shop)->sendRequest(new Request('GET', 'http://8.8.8.8/api/_info/config'));
            static::fail('Expected the request to be refused');
        } catch (NetworkExceptionInterface $e) {
            static::assertInstanceOf(ShopRequestRefusedException::class, $e->getPrevious());
        }

        static::assertSame(0, $base->getRequestsCount());
    }

    public function testReachabilityCheckRefusesAPlainHttpShop(): void
    {
        $listener = $this->compile(['url_guard' => true], null, ['check_if_shop_url_is_reachable' => true])
            ->get('test.reachability_check');
        static::assertInstanceOf(BeforeRegistrationStartsListener::class, $listener);
        $shop = new MockShop('shop-id', 'http://8.8.8.8', 'shop-secret', pendingShopUrl: 'http://8.8.8.8');

        $this->expectException(ShopURLIsNotReachableException::class);

        $listener(new BeforeRegistrationStartsEvent($this->createMock(RequestInterface::class), $shop));
    }

    /**
     * @param array<string, mixed> $shopHttpClient
     * @param array<string, mixed> $config
     */
    private function compile(array $shopHttpClient, ?HttpClientInterface $baseClient = null, array $config = []): ContainerBuilder
    {
        self::$baseClient = $baseClient;

        $container = new ContainerBuilder();
        $container->register('http_client', HttpClientInterface::class)->setFactory([self::class, 'baseClient']);

        (new ShopwareAppExtension())->load([['shop_http_client' => $shopHttpClient] + $config], $container);

        // Public, so the services they need would have to be stubbed as well
        $container->removeDefinition(LifecycleController::class);
        $container->removeDefinition(WebhookController::class);

        $container->setAlias('test.shop_http_client', ShopwareAppExtension::SHOP_HTTP_CLIENT)->setPublic(true);
        $container->setAlias('test.client_factory', ClientFactory::class)->setPublic(true);
        $container->setAlias('test.reachability_check', BeforeRegistrationStartsListener::class)->setPublic(true);

        $container->compile();

        return $container;
    }

    private static function innerClient(HttpClientInterface $client): HttpClientInterface
    {
        $inner = (new \ReflectionProperty($client, 'client'))->getValue($client);
        static::assertInstanceOf(HttpClientInterface::class, $inner);

        return $inner;
    }
}
