<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use GuzzleHttp\Client;
use Shopware\AppBundle\Shop\ShopEntity;

class GuzzleClientFactory implements ClientFactoryInterface
{
    public function createClient(ShopEntity $shop): ShopClient
    {
        return new ShopClient(
            new Client($this->getClientConfiguration($shop)),
            $shop
        );
    }

    protected function getClientConfiguration(ShopEntity $shop): array
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
