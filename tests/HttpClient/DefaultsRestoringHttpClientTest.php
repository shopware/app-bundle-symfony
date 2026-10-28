<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\HttpClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\HttpClient\DefaultsRestoringHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * How it restores the defaults behind ShopUrlGuardHttpClient is covered there.
 */
#[CoversClass(DefaultsRestoringHttpClient::class)]
final class DefaultsRestoringHttpClientTest extends TestCase
{
    public function testLeavesARequestWithoutTheListOfRequestedOptionsAlone(): void
    {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = $options;

            return new MockResponse('{}');
        }))->withOptions(['timeout' => 3]);

        (new DefaultsRestoringHttpClient($inner))->request('GET', 'https://shop.example.com/api/x', [
            'timeout' => 1,
            'extra' => ['curl' => [CURLOPT_TCP_KEEPALIVE => 1]],
        ])->getContent();

        static::assertSame(1.0, $sent['timeout']);
        static::assertSame([CURLOPT_TCP_KEEPALIVE => 1], $sent['extra']['curl']);
    }

    /**
     * NoPrivateNetworkHttpClient sets these on every hop, and its checks depend on them: a `max_redirects` or
     * `resolve` default of the decorated client must not take their place.
     */
    public function testNeverRemovesWhatShapesTheRequestOrKeepsItGuarded(): void
    {
        $sent = [];
        $inner = (new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = $options;

            return new MockResponse('{}');
        }))->withOptions([
            'max_redirects' => 20,
            'resolve' => ['shop.example.com' => '1.1.1.1'],
            'user_data' => 'decorated client',
            'buffer' => true,
        ]);
        $onProgress = static function (): void {
        };

        (new DefaultsRestoringHttpClient($inner))->request('POST', 'https://shop.example.com/api/x', [
            'max_redirects' => 0,
            'resolve' => ['shop.example.com' => '8.8.8.8'],
            'on_progress' => $onProgress,
            'user_data' => 'request',
            'buffer' => false,
            'headers' => ['Authorization' => 'Bearer shop-access-token'],
            'body' => 'payload',
            'extra' => [DefaultsRestoringHttpClient::REQUESTED_OPTIONS => DefaultsRestoringHttpClient::requestedOptions([], [])],
        ])->getContent();

        static::assertSame(0, $sent['max_redirects']);
        static::assertSame(['shop.example.com' => '8.8.8.8'], $sent['resolve']);
        static::assertSame($onProgress, $sent['on_progress']);
        static::assertSame('request', $sent['user_data']);
        static::assertFalse($sent['buffer']);
        static::assertSame(['Authorization: Bearer shop-access-token'], $sent['normalized_headers']['authorization']);
        static::assertSame('payload', $sent['body']);
        static::assertArrayNotHasKey(DefaultsRestoringHttpClient::REQUESTED_OPTIONS, $sent['extra']);
    }
}
