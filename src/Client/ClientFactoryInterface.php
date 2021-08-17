<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use Shopware\AppBundle\Shop\ShopEntity;

interface ClientFactoryInterface
{
    public function createClient(ShopEntity $shop): ShopClient;
}
