<?php

declare(strict_types=1);

namespace Shopware\AppBundle\HttpClient;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Sits beneath {@see NoPrivateNetworkHttpClient} in {@see ShopUrlGuardHttpClient}, which fills every option a request
 * leaves out with Symfony's default, `extra` ones included. Those would override the defaults of the decorated client,
 * e.g. `framework.http_client.default_options`, so the transport options the request did not set itself, as listed
 * in its `extra` option, are removed again.
 *
 * @internal
 */
final class DefaultsRestoringHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    /**
     * Key of the `extra` option that lists the options and the `extra` keys a request set itself, as
     * {@see self::requestedOptions()} builds it.
     */
    public const REQUESTED_OPTIONS = 'shopware_app_requested_options';

    /**
     * Options that tune the transport rather than shape the request.
     * The ones {@see NoPrivateNetworkHttpClient} relies on, such as `resolve`, `max_redirects` and `on_progress`, are not among them.
     */
    private const TRANSPORT_OPTIONS = [
        'http_version',
        'proxy',
        'no_proxy',
        'timeout',
        'max_duration',
        'max_connect_duration',
        'bindto',
        'verify_peer',
        'verify_host',
        'cafile',
        'capath',
        'local_cert',
        'local_pk',
        'passphrase',
        'ciphers',
        'peer_fingerprint',
        'capture_peer_cert_chain',
        'crypto_method',
    ];

    /**
     * @param array<string, mixed> $options
     * @param array<array-key, mixed> $extra
     *
     * @return array{options: list<string>, extra: list<array-key>}
     */
    public static function requestedOptions(array $options, array $extra): array
    {
        return ['options' => array_keys($options), 'extra' => array_keys($extra)];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $extra = $options['extra'] ?? null;

        if (is_array($extra) && is_array($extra[self::REQUESTED_OPTIONS] ?? null)) {
            /** @var array{options: list<string>, extra: list<array-key>} $requested */
            $requested = $extra[self::REQUESTED_OPTIONS];
            unset($extra[self::REQUESTED_OPTIONS]);

            foreach (array_diff(array_keys(self::OPTIONS_DEFAULTS['extra']), $requested['extra']) as $key) {
                unset($extra[$key]);
            }

            $options['extra'] = $extra;

            foreach (array_diff(self::TRANSPORT_OPTIONS, $requested['options']) as $option) {
                unset($options[$option]);
            }
        }

        return $this->client->request($method, $url, $options);
    }
}
