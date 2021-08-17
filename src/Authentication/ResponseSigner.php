<?php declare(strict_types=1);

namespace Shopware\AppBundle\Authentication;

use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Metadata;
use Shopware\AppBundle\Shop\ShopEntity;

class ResponseSigner
{
    public function __construct(
        private Metadata $metadata
    ) {
    }

    public function getRegistrationSignature(ShopEntity $shop): string
    {
        return $this->sign($shop->getId() . $shop->getUrl() . $this->metadata->getName(), $shop->getShopSecret());
    }

    public function signResponse(ResponseInterface $response, ShopEntity $shop): ResponseInterface
    {
        $content = $response->getBody()->getContents();
        $response->getBody()->rewind();

        return $response->withHeader(
            'shopware-app-signature',
            $this->sign($content, $shop->getShopSecret())
        );
    }

    private function sign(string $message, string $secret): string
    {
        return hash_hmac('sha256', $message, $secret);
    }
}
