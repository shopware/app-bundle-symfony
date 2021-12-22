<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use GuzzleHttp\Client;
use Shopware\AppBundle\Shop\ShopInterface;

class GuzzleClientFactory implements ClientFactoryInterface
{
    public function createClient(ShopInterface $shop): ShopClient
    {
        return new ShopClient(
            new Client($this->getClientConfiguration($shop)),
            $shop
        );
    }

    protected function getClientConfiguration(ShopInterface $shop): array
    {
        return [
            'base_uri' => $this->ensureTrailingSlashInUrl($shop->getUrl()),
        ];
    }

    private function ensureTrailingSlashInURL(string $shopUrl): string
    {
        return str_ends_with($shopUrl, '/') ? $shopUrl : $shopUrl . '/';
    }
}
