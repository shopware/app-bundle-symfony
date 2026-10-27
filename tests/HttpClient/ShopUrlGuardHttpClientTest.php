<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\HttpClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\Exception\ShopRequestRefusedException;
use Shopware\AppBundle\HttpClient\DefaultsRestoringHttpClient;
use Shopware\AppBundle\HttpClient\ShopUrlGuardHttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(ShopUrlGuardHttpClient::class)]
#[CoversClass(DefaultsRestoringHttpClient::class)]
#[CoversClass(ShopRequestRefusedException::class)]
final class ShopUrlGuardHttpClientTest extends TestCase
{
    private const DEFAULT_OPTIONS = [
        'max_duration' => 9,
        'timeout' => 3,
        'verify_peer' => false,
        'http_version' => '1.1',
        'extra' => ['use_persistent_connections' => true, 'curl' => [CURLOPT_TCP_KEEPALIVE => 1]],
    ];

    /**
     * Public IP literals throughout, so no test depends on DNS.
     */
    public function testSendsAnHttpsRequestToAPublicAddress(): void
    {
        $client = new ShopUrlGuardHttpClient(new MockHttpClient(new MockResponse('{"ok":true}')));

        static::assertSame('{"ok":true}', $client->request('GET', 'https://8.8.8.8/api/x')->getContent());
    }

    public function testReadsTheSchemeInAnyCase(): void
    {
        $client = new ShopUrlGuardHttpClient(new MockHttpClient(new MockResponse('{"ok":true}')));

        static::assertSame('{"ok":true}', $client->request('GET', 'HTTPS://8.8.8.8/api/x')->getContent());
    }

    public function testLetsAnUnrelatedTransportFailureThrough(): void
    {
        $client = new ShopUrlGuardHttpClient(new MockHttpClient(new MockResponse('', ['error' => 'Connection refused'])));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Connection refused');

        $client->request('GET', 'https://8.8.8.8/api/x')->getContent();
    }

    #[DataProvider('blockedAddresses')]
    public function testRefusesABlockedAddressBeforeItReachesTheTransport(string $url): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));

        try {
            (new ShopUrlGuardHttpClient($inner))->request('GET', $url);
            static::fail('Expected the address to be refused');
        } catch (TransportExceptionInterface $e) {
            static::assertStringContainsString('is blocked for', $e->getMessage());
        }

        static::assertSame(0, $inner->getRequestsCount());
    }

    public static function blockedAddresses(): \Generator
    {
        yield 'loopback by name' => ['https://localhost/api/x'];
        yield 'cloud metadata' => ['https://169.254.169.254/latest/meta-data/'];
        yield 'shared address space' => ['https://100.64.0.1/api/x'];
        yield 'IETF protocol assignments' => ['https://192.0.0.1/api/x'];
        yield 'IPv4 multicast' => ['https://224.0.0.1/api/x'];
        yield 'IPv6 loopback' => ['https://[::1]/api/x'];
        yield 'NAT64 of a link-local address' => ['https://[64:ff9b::a9fe:a9fe]/api/x'];
        yield 'IPv6 multicast' => ['https://[ff02::1]/api/x'];
    }

    public function testRefusesPlainHttpBeforeItReachesTheTransport(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));

        try {
            (new ShopUrlGuardHttpClient($inner))->request('POST', 'http://8.8.8.8/api/oauth/token');
            static::fail('Expected the request to be refused');
        } catch (ShopRequestRefusedException $e) {
            static::assertSame(
                'Request to shop URL "http://8.8.8.8/api/oauth/token" refused: only https is allowed, got "http".',
                $e->getMessage()
            );
        }

        static::assertSame(0, $inner->getRequestsCount());
    }

    public function testRefusesAUrlWithoutScheme(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));

        $this->expectException(ShopRequestRefusedException::class);
        $this->expectExceptionMessage('only https is allowed, got "".');

        (new ShopUrlGuardHttpClient($inner))->request('GET', 'shop.example.com/api/x');
    }

    public function testRefusesARedirectToPlainHttp(): void
    {
        $inner = new MockHttpClient([
            new MockResponse('', ['http_code' => 302, 'redirect_url' => 'http://8.8.4.4/api/x']),
            new MockResponse('{}'),
        ]);
        $response = (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x');

        static::assertSame(
            'Request to shop URL "http://8.8.4.4/api/x" refused: only https is allowed, got "http".',
            $this->refusalWhileReading($response)->getMessage()
        );
    }

    public function testRefusesARedirectToABlockedAddress(): void
    {
        $inner = new MockHttpClient([
            new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://127.0.0.1/api/x']),
            new MockResponse('{}'),
        ]);
        $response = (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x');

        $this->expectException(TransportExceptionInterface::class);
        $this->expectExceptionMessage('Host "127.0.0.1" is blocked for "https://127.0.0.1/api/x".');

        $response->getContent();
    }

    /**
     * A ceiling, not a default: a request may ask for fewer redirects but never for more.
     */
    public function testCapsRedirectsWithoutRaisingALowerRequestValue(): void
    {
        $requests = 0;
        $inner = new MockHttpClient(static function () use (&$requests): MockResponse {
            ++$requests;

            return new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://8.8.4.4/api/x']);
        });
        $client = new ShopUrlGuardHttpClient($inner, maxRedirects: 2);

        $followed = static function (array $options) use ($client, &$requests): int {
            $requests = 0;
            $client->request('GET', 'https://8.8.8.8/api/x', $options)->getStatusCode();

            return $requests - 1;
        };

        static::assertSame(2, $followed([]));
        static::assertSame(2, $followed(['max_redirects' => 20]));
        static::assertSame(1, $followed(['max_redirects' => 1]));
        static::assertSame(0, $followed(['max_redirects' => 0]));
    }

    public function testRefusesAResponseAnnouncedLargerThanTheCap(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}', ['response_headers' => ['Content-Length: 11']]));
        $response = (new ShopUrlGuardHttpClient($inner, maxResponseBytes: 10))->request('GET', 'https://8.8.8.8/api/x');

        static::assertSame(
            'Response from shop URL "https://8.8.8.8/api/x" refused: it exceeds 10 bytes.',
            $this->refusalWhileReading($response)->getMessage()
        );
    }

    public function testRefusesABodyLargerThanTheCap(): void
    {
        $inner = new MockHttpClient(new MockResponse(str_repeat('x', 11)));
        $response = (new ShopUrlGuardHttpClient($inner, maxResponseBytes: 10))->request('GET', 'https://8.8.8.8/api/x');

        static::assertStringContainsString('it exceeds 10 bytes', $this->refusalWhileReading($response)->getMessage());
    }

    public function testAcceptsAResponseOfExactlyTheCap(): void
    {
        $body = str_repeat('x', 10);
        $inner = new MockHttpClient(new MockResponse($body, ['response_headers' => ['Content-Length: 10']]));

        $response = (new ShopUrlGuardHttpClient($inner, maxResponseBytes: 10))->request('GET', 'https://8.8.8.8/api/x');

        static::assertSame($body, $response->getContent());
    }

    public function testPassesProgressOnToTheCallback(): void
    {
        $downloaded = [];
        $client = new ShopUrlGuardHttpClient(new MockHttpClient(new MockResponse('{"ok":true}')));

        $client->request('GET', 'https://8.8.8.8/api/x', [
            'on_progress' => static function (int $dlNow) use (&$downloaded): void {
                $downloaded[] = $dlNow;
            },
        ])->getContent();

        static::assertContains(11, $downloaded);
    }

    /**
     * NoPrivateNetworkHttpClient fills every option a request leaves out, which would override these, e.g. as
     * `framework.http_client.default_options` sets them.
     */
    public function testKeepsTheDefaultOptionsOfTheDecoratedClientOnEveryHop(): void
    {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent[] = $options;

            return \count($sent) === 1
                ? new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://8.8.4.4/api/x'])
                : new MockResponse('{}');
        }))->withOptions(self::DEFAULT_OPTIONS);

        (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x')->getContent();

        static::assertCount(2, $sent);

        foreach ($sent as $options) {
            static::assertSame(9.0, $options['max_duration']);
            static::assertSame(3.0, $options['timeout']);
            static::assertFalse($options['verify_peer']);
            static::assertSame('1.1', $options['http_version']);
            static::assertSame(['use_persistent_connections' => true, 'curl' => [CURLOPT_TCP_KEEPALIVE => 1]], $options['extra']);
        }
    }

    public function testRequestOptionsStillOverrideTheDefaultOptions(): void
    {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = $options;

            return new MockResponse('{}');
        }))->withOptions(self::DEFAULT_OPTIONS);

        (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x', [
            'timeout' => 1,
            'verify_peer' => true,
            'extra' => ['use_persistent_connections' => false],
        ])->getContent();

        static::assertSame(1.0, $sent['timeout']);
        static::assertTrue($sent['verify_peer']);
        static::assertSame(9.0, $sent['max_duration']);
        static::assertSame(['use_persistent_connections' => false, 'curl' => [CURLOPT_TCP_KEEPALIVE => 1]], $sent['extra']);
    }

    /**
     * Raised from `on_progress`, the refusal surfaces as-is or wrapped, depending on where the transport calls it.
     */
    private function refusalWhileReading(ResponseInterface $response): ShopRequestRefusedException
    {
        try {
            $response->getContent();
            static::fail('Expected the response to be refused');
        } catch (ShopRequestRefusedException $e) {
            return $e;
        } catch (TransportExceptionInterface $e) {
            $refusal = $e->getPrevious();
        }

        static::assertInstanceOf(ShopRequestRefusedException::class, $refusal);

        return $refusal;
    }
}
