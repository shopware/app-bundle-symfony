<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

interface ShopRepositoryInterface
{
    public function createShop(ShopInterface $shop): void;

    public function getShopFromId(string $shopId): ShopInterface|null;

    public function updateShop(ShopInterface $shop): void;

    public function deleteShop(ShopInterface $shop): void;
}
