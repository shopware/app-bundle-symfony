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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(ShopUrlGuardHttpClient::class)]
#[CoversClass(DefaultsRestoringHttpClient::class)]
#[CoversClass(ShopRequestRefusedException::class)]
final class ShopUrlGuardHttpClientTest extends TestCase
{
    /**
     * A value apart from Symfony's own default for every option that tunes the transport.
     */
    private const DEFAULT_OPTIONS = [
        'http_version' => '1.1',
        'proxy' => 'http://proxy.example.com:3128',
        'no_proxy' => 'internal.example.com',
        'timeout' => 3,
        'max_duration' => 9,
        'max_connect_duration' => 4,
        'bindto' => '0:0',
        'verify_peer' => false,
        'verify_host' => false,
        'cafile' => '/etc/ssl/shop-ca.pem',
        'capath' => '/etc/ssl/certs',
        'local_cert' => '/etc/ssl/app.pem',
        'local_pk' => '/etc/ssl/app.key',
        'passphrase' => 'secret',
        'ciphers' => 'ECDHE-RSA-AES256-GCM-SHA384',
        'peer_fingerprint' => ['sha256' => '0000000000000000000000000000000000000000000000000000000000000000'],
        'capture_peer_cert_chain' => true,
        'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
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

    /**
     * One address in every blocked range, then the notations that try to sneak one past a check.
     */
    public static function blockedAddresses(): \Generator
    {
        yield '0.0.0.0/8 "this network"' => ['https://0.1.2.3/api/x'];
        yield '10.0.0.0/8 private' => ['https://10.1.2.3/api/x'];
        yield '100.64.0.0/10 shared address space' => ['https://100.127.255.255/api/x'];
        yield '127.0.0.0/8 loopback' => ['https://127.0.0.1/api/x'];
        yield '169.254.0.0/16 link-local, cloud metadata' => ['https://169.254.169.254/latest/meta-data/'];
        yield '172.16.0.0/12 private' => ['https://172.31.255.255/api/x'];
        yield '192.0.0.0/24 IETF protocol assignments' => ['https://192.0.0.1/api/x'];
        yield '192.0.2.0/24 documentation' => ['https://192.0.2.1/api/x'];
        yield '192.168.0.0/16 private' => ['https://192.168.1.1/api/x'];
        yield '198.18.0.0/15 benchmarking' => ['https://198.19.255.255/api/x'];
        yield '198.51.100.0/24 documentation' => ['https://198.51.100.1/api/x'];
        yield '203.0.113.0/24 documentation' => ['https://203.0.113.1/api/x'];
        yield '224.0.0.0/4 multicast' => ['https://239.255.255.255/api/x'];
        yield '240.0.0.0/4 reserved, broadcast' => ['https://255.255.255.255/api/x'];
        yield '::/128 unspecified' => ['https://[::]/api/x'];
        yield '::1/128 loopback' => ['https://[::1]/api/x'];
        yield '::/96 IPv4-compatible' => ['https://[::7f00:1]/api/x'];
        yield '::ffff:0:0/96 IPv4-mapped' => ['https://[::ffff:127.0.0.1]/api/x'];
        yield '64:ff9b::/96 NAT64' => ['https://[64:ff9b::a9fe:a9fe]/api/x'];
        yield '64:ff9b:1::/48 local NAT64' => ['https://[64:ff9b:1::a00:1]/api/x'];
        yield '2001::/32 Teredo' => ['https://[2001::1]/api/x'];
        yield '2001:2::/48 benchmarking' => ['https://[2001:2::1]/api/x'];
        yield '2001:db8::/32 documentation' => ['https://[2001:db8::1]/api/x'];
        yield '2002::/16 6to4' => ['https://[2002:7f00:1::]/api/x'];
        yield 'fc00::/7 unique local' => ['https://[fd00::1]/api/x'];
        yield 'fe80::/10 link-local' => ['https://[fe80::1]/api/x'];
        yield 'fec0::/10 site-local' => ['https://[fec0::1]/api/x'];
        yield 'ff00::/8 multicast' => ['https://[ff02::1]/api/x'];

        yield 'loopback by name' => ['https://localhost/api/x'];
        yield 'loopback by name with a trailing dot' => ['https://localhost./api/x'];
        yield 'loopback in decimal' => ['https://2130706433/api/x'];
        yield 'loopback in octal' => ['https://0177.0.0.1/api/x'];
        yield 'loopback shortened' => ['https://127.1/api/x'];
        yield 'loopback behind a public address as user info' => ['https://8.8.8.8@127.0.0.1/api/x'];
        yield 'loopback before a fragment naming a public address' => ['https://127.0.0.1#@8.8.8.8/api/x'];
        yield 'loopback mapped into IPv6, written out' => ['https://[0:0:0:0:0:ffff:7f00:1]/api/x'];
    }

    /**
     * Next to a blocked range, so a range that was written too wide shows.
     */
    #[DataProvider('publicNeighbours')]
    public function testSendsToAPublicAddressNextToABlockedRange(string $url): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));

        (new ShopUrlGuardHttpClient($inner))->request('GET', $url)->getContent();

        static::assertSame(1, $inner->getRequestsCount());
    }

    public static function publicNeighbours(): \Generator
    {
        yield 'below 10.0.0.0/8' => ['https://9.255.255.255/api/x'];
        yield 'above 10.0.0.0/8' => ['https://11.0.0.0/api/x'];
        yield 'below 100.64.0.0/10' => ['https://100.63.255.255/api/x'];
        yield 'above 100.64.0.0/10' => ['https://100.128.0.0/api/x'];
        yield 'below 172.16.0.0/12' => ['https://172.15.255.255/api/x'];
        yield 'above 172.16.0.0/12' => ['https://172.32.0.0/api/x'];
        yield 'between 192.0.0.0/24 and 192.0.2.0/24' => ['https://192.0.1.1/api/x'];
        yield 'below 198.18.0.0/15' => ['https://198.17.255.255/api/x'];
        yield 'above 198.18.0.0/15' => ['https://198.20.0.0/api/x'];
        yield 'below 224.0.0.0/4' => ['https://223.255.255.255/api/x'];
        yield 'beside 2001::/32' => ['https://[2001:4860:4860::8888]/api/x'];
        yield 'above 2002::/16' => ['https://[2003::1]/api/x'];
        yield 'public IPv6' => ['https://[2606:4700:4700::1111]/api/x'];
    }

    /**
     * A host the request itself resolves is checked by the address it resolves to.
     */
    public function testRefusesAHostTheRequestResolvesToABlockedAddress(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));

        try {
            (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://shop.example.com/api/x', [
                'resolve' => ['shop.example.com' => '10.0.0.1'],
            ]);
            static::fail('Expected the address to be refused');
        } catch (TransportExceptionInterface $e) {
            static::assertSame('Host "shop.example.com" is blocked for "https://shop.example.com/api/x".', $e->getMessage());
        }

        static::assertSame(0, $inner->getRequestsCount());
    }

    /**
     * The transport connects to the address that was checked, not to whatever the host resolves to by then.
     */
    public function testPinsTheCheckedAddress(): void
    {
        $sent = [];
        $inner = new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = $options;

            return new MockResponse('{}');
        });

        (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://shop.example.com/api/x', [
            'resolve' => ['shop.example.com' => '8.8.8.8'],
        ])->getContent();

        static::assertSame(['shop.example.com' => '8.8.8.8'], $sent['resolve']);
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

    public function testRefusesARedirectToABlockedAddressBeforeItReachesTheTransport(): void
    {
        $inner = new MockHttpClient([
            new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://127.0.0.1/api/x']),
            new MockResponse('{}'),
        ]);
        $response = (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x');

        try {
            $response->getContent();
            static::fail('Expected the redirect to be refused');
        } catch (TransportExceptionInterface $e) {
            static::assertSame('Host "127.0.0.1" is blocked for "https://127.0.0.1/api/x".', $e->getMessage());
        }

        static::assertSame(1, $inner->getRequestsCount());
    }

    /**
     * The guard follows redirects itself, checking every hop. A transport following them on its own, e.g. because
     * of a `max_redirects` default, would skip that check.
     */
    public function testLeavesNoRedirectToTheTransport(): void
    {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent[] = $options;

            return \count($sent) === 1
                ? new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://8.8.4.4/api/x'])
                : new MockResponse('{}');
        }))->withOptions(['max_redirects' => 20]);

        (new ShopUrlGuardHttpClient($inner))->request('GET', 'https://8.8.8.8/api/x')->getContent();

        static::assertSame([0, 0], array_column($sent, 'max_redirects'));
    }

    /**
     * The SDK sends the shop's access token as a header, which a shop must not be able to redirect to another host.
     */
    public function testDropsTheAuthorizationHeaderOnARedirectToAnotherHost(): void
    {
        $sent = $this->sentAlongARedirect('GET', 'https://8.8.4.4/api/x', 302, [
            'headers' => ['Authorization' => 'Bearer shop-access-token'],
        ]);

        static::assertArrayHasKey('authorization', $sent[0]['normalized_headers']);
        static::assertArrayNotHasKey('authorization', $sent[1]['normalized_headers']);
    }

    public function testKeepsTheAuthorizationHeaderOnARedirectWithinTheShop(): void
    {
        $sent = $this->sentAlongARedirect('GET', 'https://8.8.8.8/api/moved', 302, [
            'headers' => ['Authorization' => 'Bearer shop-access-token'],
        ]);

        static::assertSame(['Authorization: Bearer shop-access-token'], $sent[1]['normalized_headers']['authorization']);
    }

    /**
     * Repeating a write after a 303 would run it against the shop twice.
     */
    public function testTurnsAPostIntoAGetWithoutBodyOnA303(): void
    {
        $sent = $this->sentAlongARedirect('POST', 'https://8.8.8.8/api/result', 303, ['body' => 'client_secret=secret']);

        static::assertSame(['POST', 'GET'], array_column($sent, 'method'));
        static::assertEmpty($sent[1]['body'] ?? null);
    }

    public function testRepeatsAPostWithItsBodyOnA307(): void
    {
        $sent = $this->sentAlongARedirect('POST', 'https://8.8.8.8/api/moved', 307, ['body' => 'client_secret=secret']);

        static::assertSame(['POST', 'POST'], array_column($sent, 'method'));
        static::assertSame('client_secret=secret', $sent[1]['body']);
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
        // Compared with what the decorated client gets without the guard in between
        $defaults = self::defaultOptions();
        $expected = $this->sentAlongARedirect('GET', 'https://8.8.4.4/api/x', 302, [], $defaults, guarded: false)[0];

        $sent = $this->sentAlongARedirect('GET', 'https://8.8.4.4/api/x', 302, [], $defaults);

        static::assertCount(2, $sent);

        foreach ($sent as $hop => $options) {
            foreach (array_keys($defaults) as $option) {
                static::assertSame($expected[$option], $options[$option], sprintf('"%s" on hop %d', $option, $hop));
            }
        }
    }

    /**
     * Also when they set what Symfony's default would be, which NoPrivateNetworkHttpClient fills in as well.
     */
    public function testRequestOptionsStillOverrideTheDefaultOptions(): void
    {
        $sent = $this->sentAlongARedirect('GET', 'https://8.8.4.4/api/x', 302, [
            'timeout' => 1,
            'max_duration' => 0,
            'verify_peer' => true,
            'extra' => ['use_persistent_connections' => false],
        ], self::defaultOptions());

        foreach ($sent as $options) {
            static::assertSame(1.0, $options['timeout']);
            static::assertSame(0.0, $options['max_duration']);
            static::assertTrue($options['verify_peer']);
            static::assertSame('1.1', $options['http_version']);
            static::assertSame(['use_persistent_connections' => false, 'curl' => [CURLOPT_TCP_KEEPALIVE => 1]], $options['extra']);
        }
    }

    /**
     * withOptions() is how a scope or a decorator applies options to the guard.
     */
    public function testKeepsGuardingAClientWithOptions(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));
        $client = (new ShopUrlGuardHttpClient($inner))->withOptions(['timeout' => 1]);

        static::assertInstanceOf(ShopUrlGuardHttpClient::class, $client);

        foreach (['http://8.8.8.8/api/x', 'https://127.0.0.1/api/x'] as $url) {
            try {
                $client->request('GET', $url);
                static::fail(sprintf('Expected "%s" to be refused', $url));
            } catch (TransportExceptionInterface) {
            }
        }

        static::assertSame(0, $inner->getRequestsCount());
    }

    public function testResetsTheDecoratedClient(): void
    {
        $inner = new MockHttpClient(new MockResponse('{}'));
        $client = new ShopUrlGuardHttpClient($inner);
        $client->request('GET', 'https://8.8.8.8/api/x')->getContent();

        $client->reset();

        static::assertSame(0, $inner->getRequestsCount());
    }

    /**
     * The ones the installed Symfony knows.
     *
     * @return array<string, mixed>
     */
    private static function defaultOptions(): array
    {
        return array_intersect_key(self::DEFAULT_OPTIONS, HttpClientInterface::OPTIONS_DEFAULTS);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $defaultOptions of the decorated client
     *
     * @return list<array<string, mixed>> the options of each request that reached the decorated client, with its method
     */
    private function sentAlongARedirect(
        string $method,
        string $location,
        int $status,
        array $options,
        array $defaultOptions = [],
        bool $guarded = true,
    ): array {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent, $location, $status): MockResponse {
            $sent[] = ['method' => $method] + $options;

            return \count($sent) === 1
                ? new MockResponse('', ['http_code' => $status, 'redirect_url' => $location])
                : new MockResponse('{}');
        }))->withOptions($defaultOptions);

        $client = $guarded ? new ShopUrlGuardHttpClient($inner) : $inner;
        $client->request($method, 'https://8.8.8.8/api/x', $options)->getContent(false);

        return $sent;
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
