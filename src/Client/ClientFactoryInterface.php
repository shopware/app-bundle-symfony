<?php declare(strict_types=1);

namespace Shopware\AppBundle\Client;

use Shopware\AppBundle\Shop\ShopInterface;

interface ClientFactoryInterface
{
    public function createClient(ShopInterface $shop): ShopClient;
}
