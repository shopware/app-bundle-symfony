<?php

declare(strict_types=1);

namespace Shopware\AppBundle\HttpClient;

use Shopware\AppBundle\Exception\ShopRequestRefusedException;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Applies the shop request policy to every hop, including the ones a redirect adds: public addresses and
 * https only, a ceiling on redirects and on the response size.
 *
 * Hop mechanics are {@see NoPrivateNetworkHttpClient}'s. It leaves the scheme alone, and a 307/308 to http
 * would replay the body, e.g. the token request's client secret, in plain text.
 *
 * The default options of the decorated client still apply, see {@see DefaultsRestoringHttpClient}.
 */
final class ShopUrlGuardHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    public const MAX_REDIRECTS = 5;

    /**
     * Roomy enough for a real entity-search result set, while still bounding one response.
     */
    public const MAX_RESPONSE_BYTES = 8 * 1024 * 1024;

    /**
     * Blocked on top of {@see IpUtils::PRIVATE_SUBNETS}, which lacks them in some or all of the Symfony versions
     * this bundle supports: older releases only carry the private, loopback, link-local and reserved ranges.
     */
    private const BLOCKED_SUBNETS = [
        '100.64.0.0/10',   // shared address space, CGNAT (RFC 6598)
        '192.0.0.0/24',    // IETF protocol assignments (RFC 6890)
        '192.0.2.0/24',    // documentation TEST-NET-1 (RFC 5737)
        '198.18.0.0/15',   // benchmarking (RFC 2544)
        '198.51.100.0/24', // documentation TEST-NET-2 (RFC 5737)
        '203.0.113.0/24',  // documentation TEST-NET-3 (RFC 5737)
        '224.0.0.0/4',     // multicast
        '::/96',           // IPv4-compatible (RFC 4291)
        '64:ff9b::/96',    // NAT64 well-known prefix (RFC 6052)
        '64:ff9b:1::/48',  // NAT64 local-use prefix (RFC 8215)
        '2001::/32',       // Teredo (RFC 4380)
        '2001:2::/48',     // benchmarking (RFC 5180)
        '2001:db8::/32',   // documentation (RFC 3849)
        '2002::/16',       // 6to4 (RFC 3056)
        'fec0::/10',       // deprecated site-local
        'ff00::/8',        // multicast
    ];

    /**
     * @param int $maxRedirects a ceiling, not a default: a request may ask for fewer redirects but never for more
     */
    public function __construct(
        HttpClientInterface $client,
        private readonly int $maxRedirects = self::MAX_REDIRECTS,
        private readonly int $maxResponseBytes = self::MAX_RESPONSE_BYTES,
    ) {
        $this->client = new NoPrivateNetworkHttpClient(
            new DefaultsRestoringHttpClient($client),
            [...IpUtils::PRIVATE_SUBNETS, ...self::BLOCKED_SUBNETS],
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ShopRequestRefusedException when the URL is not https
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        self::assertHttps($url);

        $options['max_redirects'] = is_numeric($options['max_redirects'] ?? null)
            ? min((int) $options['max_redirects'], $this->maxRedirects)
            : $this->maxRedirects;

        $maxResponseBytes = $this->maxResponseBytes;
        /** @var (callable(int, int, array<string, mixed>): void)|null $callerProgress */
        $callerProgress = $options['on_progress'] ?? null;

        // Fires per hop before that hop is written, so refusing here means nothing was sent
        $options['on_progress'] = static function (int $dlNow, int $dlSize, array $info) use ($maxResponseBytes, $callerProgress): void {
            $hopUrl = is_string($info['url'] ?? null) ? $info['url'] : '';

            if ($hopUrl !== '') {
                self::assertHttps($hopUrl);
            }

            // `$dlSize` is the announced length, so an oversized response is refused unread.
            if ($dlNow > $maxResponseBytes || $dlSize > $maxResponseBytes) {
                throw ShopRequestRefusedException::responseTooLarge($hopUrl, $maxResponseBytes);
            }

            if ($callerProgress !== null) {
                $callerProgress($dlNow, $dlSize, $info);
            }
        };

        $extra = $options['extra'] ?? [];

        if (is_array($extra)) {
            $options['extra'] = [
                DefaultsRestoringHttpClient::REQUESTED_OPTIONS => DefaultsRestoringHttpClient::requestedOptions($options, $extra),
            ] + $extra;
        }

        return $this->client->request($method, $url, $options);
    }

    private static function assertHttps(string $url): void
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'https') {
            throw ShopRequestRefusedException::insecureScheme($url, $scheme);
        }
    }
}
