<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use GuzzleHttp\Client;
use Shopware\AppBundle\Shop\ShopEntity;

class GuzzleClientFactory implements ClientFactoryInterface
{
    public function createClient(ShopEntity $shop): ShopClient
    {
        return new ShopClient(
            new Client([
                'base_uri' => $shop->getUrl(),
            ]),
            $shop
        );
    }
}
