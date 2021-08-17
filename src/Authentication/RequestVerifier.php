<?php declare(strict_types=1);

namespace Shopware\AppBundle\Authentication;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;
use Shopware\AppBundle\Exception\SignatureNotFoundException;
use Shopware\AppBundle\Exception\SignatureValidationException;
use Shopware\AppBundle\Shop\ShopEntity;

class RequestVerifier
{
    private const SHOPWARE_SHOP_SIGNATURE_HEADER = 'shopware-shop-signature';

    private const SHOPWARE_APP_SIGNATURE_HEADER = 'shopware-app-signature';

    public function authenticateRegistrationRequest(RequestInterface $request, string $appSecret): void
    {
        $signature = $this->getSignatureFromHeader($request, self::SHOPWARE_APP_SIGNATURE_HEADER);

        $queries = Query::parse($request->getUri()->getQuery());

        $this->verifySignature(
            $request,
            $appSecret,
            $this->buildValidationQuery($queries),
            $signature
        );
    }

    public function authenticatePostRequest(RequestInterface $request, ShopEntity $shop): void
    {
        $signature = $this->getSignatureFromHeader($request, self::SHOPWARE_SHOP_SIGNATURE_HEADER);

        $this->verifySignature(
            $request,
            $shop->getShopSecret(),
            $request->getBody()->getContents(),
            $signature
        );
    }

    public function authenticateGetRequest(RequestInterface $request, ShopEntity $shop): void
    {
        $queries = Query::parse($request->getUri()->getQuery());

        if (!isset($queries['shopware-shop-signature'])) {
            throw new SignatureNotFoundException($request);
        }

        $signature = $queries['shopware-shop-signature'];

        $this->verifySignature(
            $request,
            $shop->getShopSecret(),
            $this->buildValidationQuery($queries),
            $signature
        );
    }

    private function getSignatureFromHeader(RequestInterface $request, string $headerName): string
    {
        $signatureHeader = $request->getHeader($headerName);

        if (empty($signatureHeader)) {
            throw new SignatureNotFoundException($request);
        }

        return $signatureHeader[0];
    }

    private function verifySignature(RequestInterface $request, string $secret, string $message, string $signature): void
    {
        $hmac = hash_hmac('sha256', $message, $secret);

        if (!hash_equals($hmac, $signature)) {
            throw new SignatureValidationException($request);
        }
    }

    private function buildValidationQuery(array $queries): string
    {
        return sprintf(
            'shop-id=%s&shop-url=%s&timestamp=%s',
            $queries['shop-id'],
            $queries['shop-url'],
            $queries['timestamp']
        );
    }
}
