<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Test\HttpClient;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Shopware\AppBundle\HttpClient\ShutdownCancellingHttpClient;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ShutdownCancellingHttpClient::class)]
final class ShutdownCancellingHttpClientTest extends TestCase
{
    public function testCancelsAnOpenTransferThatCarriesAProgressCallback(): void
    {
        $client = new ShutdownCancellingHttpClient(new MockHttpClient(new MockResponse('{}')));
        $response = $client->request('GET', 'https://shop.example.com/api/oauth/token', ['on_progress' => static fn () => null]);

        $client->cancelOpenTransfers();

        static::assertTrue($response->getInfo('canceled'));
    }

    /**
     * Shutdown functions only run as a script ends, so a forked child ends. The client is built before the
     * fork, where coverage is recorded.
     */
    #[RequiresPhpExtension('pcntl')]
    public function testCancelsAnOpenTransferWhenTheScriptEnds(): void
    {
        $client = new ShutdownCancellingHttpClient(new MockHttpClient(new MockResponse('{}')));
        $response = $client->request('GET', 'https://shop.example.com/api/oauth/token', ['on_progress' => static fn () => null]);

        $channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        static::assertIsArray($channel);
        [$parentEnd, $childEnd] = $channel;

        $pid = pcntl_fork();
        static::assertNotSame(-1, $pid);

        if ($pid === 0) {
            fclose($parentEnd);
            // Swallows PHPUnit's notice about the test this child never finishes
            ob_start(static fn (): string => '');
            // Runs after the client's shutdown function
            register_shutdown_function(static function () use ($response, $childEnd): void {
                fwrite($childEnd, $response->getInfo('canceled') === true ? 'canceled' : 'open');
            });

            exit(0);
        }

        fclose($childEnd);
        stream_set_timeout($parentEnd, 10);
        $outcome = stream_get_contents($parentEnd);
        fclose($parentEnd);
        pcntl_waitpid($pid, $status);

        static::assertSame('canceled', $outcome);
        static::assertFalse($response->getInfo('canceled'), 'This script has not ended yet.');
    }

    public function testLeavesATransferWithoutProgressCallbackAlone(): void
    {
        $client = new ShutdownCancellingHttpClient(new MockHttpClient(new MockResponse('{}')));
        $response = $client->request('GET', 'https://shop.example.com/api/_info/config');

        $client->cancelOpenTransfers();

        static::assertFalse($response->getInfo('canceled'));
    }

    public function testCoversTheClientsWithOptionsReturns(): void
    {
        $client = new ShutdownCancellingHttpClient(new MockHttpClient(new MockResponse('{}')));
        $response = $client->withOptions(['max_duration' => 5])->request('GET', 'https://shop.example.com/api/oauth/token', ['on_progress' => static fn () => null]);

        $client->cancelOpenTransfers();

        static::assertTrue($response->getInfo('canceled'));
    }

    /**
     * A worker script serves many requests before it ends.
     */
    public function testDoesNotKeepAResponseAlive(): void
    {
        $client = new ShutdownCancellingHttpClient(new MockHttpClient(new MockResponse('{}')));
        $response = $client->request('GET', 'https://shop.example.com/api/oauth/token', ['on_progress' => static fn () => null]);
        $reference = \WeakReference::create($response);

        unset($response);
        gc_collect_cycles();

        static::assertNull($reference->get());
    }

    /**
     * Detaching a transfer that waits for its answer runs its progress callback while PHP still can.
     */
    #[RequiresPhpExtension('curl')]
    public function testDetachesACurlTransferThatIsStillWaitingForItsAnswer(): void
    {
        // Accepted from the listen backlog, never answered
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        static::assertIsResource($server, $errorMessage);
        $address = stream_socket_get_name($server, false);
        static::assertIsString($address);

        $client = new ShutdownCancellingHttpClient(new CurlHttpClient());
        $progressCalls = 0;

        try {
            $response = $client->request('POST', sprintf('http://%s/api/oauth/token', $address), [
                'on_progress' => static function () use (&$progressCalls): void {
                    ++$progressCalls;
                },
            ]);

            foreach ($client->stream($response, 0.2) as $chunk) {
                static::assertTrue($chunk->isTimeout(), 'The server never answers.');

                break;
            }

            $callsWhileWaiting = $progressCalls;

            $client->cancelOpenTransfers();

            static::assertTrue($response->getInfo('canceled'));
            static::assertGreaterThan($callsWhileWaiting, $progressCalls, 'Detaching the transfer runs its progress callback.');
        } finally {
            fclose($server);
        }
    }
}
