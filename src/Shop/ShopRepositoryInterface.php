<?php declare(strict_types=1);

namespace Shopware\AppBundle\Shop;

interface ShopRepositoryInterface
{
    public function createShop(ShopEntity $shop): void;

    public function getShopFromId(string $shopId): ShopEntity;

    public function updateShop(ShopEntity $shop): void;

    public function deleteShop(ShopEntity $shop): void;
}
