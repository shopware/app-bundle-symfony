<?php

declare(strict_types=1);

namespace Shopware\AppBundle\Exception;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * A transport failure, so Symfony's Psr18Client turns it into a PSR-18 network exception and
 * {@see \Shopware\AppBundle\EventListener\BeforeRegistrationStartsListener} reports the shop as unreachable.
 */
class ShopRequestRefusedException extends \RuntimeException implements TransportExceptionInterface
{
    public static function insecureScheme(string $url, string $scheme): self
    {
        return new self(sprintf('Request to shop URL "%s" refused: only https is allowed, got "%s".', $url, $scheme));
    }

    public static function responseTooLarge(string $url, int $maxBytes): self
    {
        return new self(sprintf('Response from shop URL "%s" refused: it exceeds %d bytes.', $url, $maxBytes));
    }
}
