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
}
