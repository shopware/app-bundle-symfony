<?php declare(strict_types=1);

namespace Shopware\AppBundle\Authentication;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;
use Shopware\AppBundle\Exception\SignatureNotFoundException;
use Shopware\AppBundle\Exception\SignatureValidationException;
use Shopware\AppBundle\Shop\ShopInterface;

class RequestVerifier
{
    private const SHOPWARE_SHOP_SIGNATURE_HEADER = 'shopware-shop-signature';

    private const SHOPWARE_APP_SIGNATURE_HEADER = 'shopware-app-signature';

    /**
     * @throws SignatureValidationException
     * @throws SignatureNotFoundException
     */
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

    /**
     * @throws SignatureValidationException
     * @throws SignatureNotFoundException
     */
    public function authenticatePostRequest(RequestInterface $request, ShopInterface $shop): void
    {
        $signature = $this->getSignatureFromHeader($request, self::SHOPWARE_SHOP_SIGNATURE_HEADER);

        $this->verifySignature(
            $request,
            $shop->getShopSecret(),
            $request->getBody()->getContents(),
            $signature
        );
    }

    /**
     * @throws SignatureValidationException
     * @throws SignatureNotFoundException
     */
    public function authenticateGetRequest(RequestInterface $request, ShopInterface $shop): void
    {
        $signature = $this->getSignatureFromQuery($request);

        $this->verifySignature(
            $request,
            $shop->getShopSecret(),
            $this->removeSignatureFromQuery($request->getUri()->getQuery(), $signature),
            $signature
        );
    }

    /**
     * @throws SignatureNotFoundException
     */
    private function getSignatureFromQuery(RequestInterface $request): string
    {
        $queries = Query::parse($request->getUri()->getQuery());

        if (!isset($queries[self::SHOPWARE_SHOP_SIGNATURE_HEADER])) {
            throw new SignatureNotFoundException($request);
        }

        return $queries[self::SHOPWARE_SHOP_SIGNATURE_HEADER];
    }

    /**
     * @throws SignatureNotFoundException
     */
    private function getSignatureFromHeader(RequestInterface $request, string $headerName): string
    {
        $signatureHeader = $request->getHeader($headerName);

        if (empty($signatureHeader)) {
            throw new SignatureNotFoundException($request);
        }

        return $signatureHeader[0];
    }

    /**
     * @throws SignatureValidationException
     */
    private function verifySignature(
        RequestInterface $request,
        string $secret,
        string $message,
        string $signature
    ): void {
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

    private function removeSignatureFromQuery(string $query, string $signature): string
    {
        return preg_replace(
            sprintf('/&%s=%s/', self::SHOPWARE_SHOP_SIGNATURE_HEADER, $signature),
            '',
            $query
        );
    }
}
