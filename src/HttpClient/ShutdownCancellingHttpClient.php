<?php

declare(strict_types=1);

namespace Shopware\AppBundle\HttpClient;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Cancels the transfers sent with an `on_progress` callback that are still open when the PHP script ends.
 * After a fatal error, executor shutdown would otherwise let libcurl call that callback once PHP no longer can,
 * which aborts the process. A fatal error inside CurlResponse::perform() stays uncovered: Symfony keeps that
 * transfer attached.
 */
final class ShutdownCancellingHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    /**
     * Shared with the clones withOptions() returns.
     *
     * @var \WeakMap<ResponseInterface, true>
     */
    private \WeakMap $withProgressCallback;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->withProgressCallback = new \WeakMap();

        register_shutdown_function(self::cancel(...), $this->withProgressCallback);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $response = $this->client->request($method, $url, $options);

        if (isset($options['on_progress'])) {
            $this->withProgressCallback[$response] = true;
        }

        return $response;
    }

    /**
     * Cancels the open transfers as the shutdown function does.
     *
     * @internal
     */
    public function cancelOpenTransfers(): void
    {
        self::cancel($this->withProgressCallback);
    }

    /**
     * @param \WeakMap<ResponseInterface, true> $responses
     */
    private static function cancel(\WeakMap $responses): void
    {
        foreach ($responses as $response => $_) {
            $response->cancel();
        }
    }
}
